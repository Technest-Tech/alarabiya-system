<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class TeacherSalaryController extends Controller
{
    public function index(Request $request): View
    {
        $monthParam = $request->get('month', Carbon::now()->format('Y-m'));
        $month = $this->resolveMonth($monthParam);

        $summaries = $this->buildSalarySummaries($month);

        $availableMonths = TeacherSalary::select('month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn ($value) => Carbon::parse($value)->format('Y-m'));

        if ($availableMonths->isEmpty()) {
            $availableMonths = collect([$month->format('Y-m')]);
        }

        // Calculate total payout in EGP (convert USD salaries if exchange rate is set)
        $exchangeRate = session('exchange_rate');
        $totalPayoutEGP = $summaries->sum(function ($summary) use ($exchangeRate) {
            $teacher = $summary['teacher'];
            $currency = $teacher->currency ?? 'EGP';
            
            if ($currency === 'USD' && $exchangeRate) {
                // If USD and exchange rate is set, use the converted amount
                return $summary['salary_amount'];
            } else {
                // Otherwise use the original amount
                return $summary['salary_amount'];
            }
        });

        return view('admin.teachers.salaries.index', [
            'summaries' => $summaries,
            'month' => $month->format('Y-m'),
            'monthLabel' => $month->isoFormat('MMMM YYYY'),
            'totalPayout' => $totalPayoutEGP,
            'availableMonths' => $availableMonths,
        ]);
    }

    public function export(Request $request)
    {
        $monthParam = $request->get('month', Carbon::now()->format('Y-m'));
        $month = $this->resolveMonth($monthParam);

        $summaries = $this->buildSalarySummaries($month);

        $fileName = sprintf('teacher-salaries-%s.csv', $month->format('Y-m'));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
        ];

        $callback = function () use ($summaries) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Teacher', 'Currency', 'Lessons', 'Total Hours', 'Hourly Rate', 'Salary Amount (EGP)', 'Status']);

            foreach ($summaries as $summary) {
                $currency = $summary['currency'] ?? 'EGP';
                fputcsv($handle, [
                    $summary['teacher']->user?->name ?? 'Unassigned',
                    $currency,
                    $summary['lessons'],
                    number_format($summary['total_hours'], 2),
                    number_format($summary['hourly_rate'], 2) . ' ' . $currency,
                    number_format($summary['salary_amount'], 2),
                    ucfirst($summary['status']),
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function markPaid(Request $request, TeacherSalary $salary): RedirectResponse
    {
        $salary->update(['status' => 'paid']);

        return redirect()->route('admin.teacher-salaries.index', [
            'month' => $request->get('month', $salary->month->format('Y-m')),
        ])->with('status', 'Teacher salary marked as paid.');
    }

    public function markUnpaid(Request $request, TeacherSalary $salary): RedirectResponse
    {
        $salary->update(['status' => 'pending']);

        return redirect()->route('admin.teacher-salaries.index', [
            'month' => $request->get('month', $salary->month->format('Y-m')),
        ])->with('status', 'Teacher salary reverted to pending.');
    }

    public function applyExchangeRate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exchange_rate' => ['required', 'numeric', 'min:0'],
            'month' => ['required', 'string'],
        ]);

        $monthParam = $validated['month'];
        $month = $this->resolveMonth($monthParam);
        $exchangeRate = (float) $validated['exchange_rate'];

        // Store exchange rate in session
        session(['exchange_rate' => $exchangeRate]);

        // Get all teachers with USD currency
        $usdTeachers = Teacher::with('user')
            ->where('currency', 'USD')
            ->get();

        $start = $month->copy()->startOfMonth();
        $monthDate = $start->toDateString();

        // Update salaries for USD teachers
        foreach ($usdTeachers as $teacher) {
            $salaryRecord = TeacherSalary::where('teacher_id', $teacher->id)
                ->whereDate('month', $monthDate)
                ->first();

            if ($salaryRecord) {
                // Calculate salary in USD
                $totalMinutes = $salaryRecord->total_minutes;
                $hourlyRate = (float) ($teacher->user?->hourly_rate ?? 0);
                $salaryAmountUSD = round(($totalMinutes / 60) * $hourlyRate, 2);
                
                // Convert to EGP using exchange rate
                $salaryAmountEGP = round($salaryAmountUSD * $exchangeRate, 2);
                
                // Update the salary record with converted amount
                $salaryRecord->update([
                    'total_amount' => $salaryAmountEGP,
                ]);
            }
        }

        return redirect()->route('admin.teacher-salaries.index', [
            'month' => $monthParam,
        ])->with('status', 'Exchange rate applied successfully to USD teachers.');
    }

    protected function buildSalarySummaries(Carbon $month): Collection
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $lessonStats = Lesson::selectRaw('teacher_id, COUNT(*) as lesson_count, SUM(duration_minutes) as total_minutes')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('teacher_id')
            ->get()
            ->keyBy('teacher_id');

        $teachers = Teacher::with('user')->get()->sortBy(function (Teacher $teacher) {
            return strtolower($teacher->user->name ?? '');
        });

        return $teachers->map(function (Teacher $teacher) use ($lessonStats, $start) {
            $stats = $lessonStats->get($teacher->id);
            $lessonCount = (int) ($stats->lesson_count ?? 0);
            $totalMinutes = (int) ($stats->total_minutes ?? 0);
            $hourlyRate = (float) ($teacher->user?->hourly_rate ?? 0);
            $currency = $teacher->currency ?? 'EGP';
            
            // Calculate salary in teacher's original currency
            $salaryAmount = round(($totalMinutes / 60) * $hourlyRate, 2);

            // Handle create/update atomically with proper error handling
            $monthDate = $start->toDateString();
            
            // Try to find existing record first
            $salaryRecord = TeacherSalary::where('teacher_id', $teacher->id)
                ->whereDate('month', $monthDate)
                ->first();

            if ($salaryRecord) {
                // Update existing record, preserve status and converted amount if it exists
                // Only update if the base calculation changed (minutes or rate)
                $newBaseAmount = round(($totalMinutes / 60) * $hourlyRate, 2);
                
                // If it's USD and we have a converted amount, keep it; otherwise update
                if ($currency === 'USD' && $salaryRecord->total_amount != $newBaseAmount) {
                    // Check if this was previously converted (amount differs from base)
                    // For now, just update with base amount - conversion will happen on Apply
                    $salaryRecord->update([
                        'total_minutes' => $totalMinutes,
                        'hourly_rate' => $hourlyRate,
                        'total_amount' => $newBaseAmount,
                    ]);
                } else {
                    $salaryRecord->update([
                        'total_minutes' => $totalMinutes,
                        'hourly_rate' => $hourlyRate,
                        'total_amount' => $salaryAmount,
                    ]);
                }
            } else {
                // Try to create new record, catch unique constraint violation
                try {
                    $salaryRecord = TeacherSalary::create([
                        'teacher_id' => $teacher->id,
                        'month' => $monthDate,
                        'total_minutes' => $totalMinutes,
                        'hourly_rate' => $hourlyRate,
                        'total_amount' => $salaryAmount,
                        'status' => 'pending',
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    // If unique constraint violation, record was created between check and create
                    // Fetch and update the existing record
                    $salaryRecord = TeacherSalary::where('teacher_id', $teacher->id)
                        ->whereDate('month', $monthDate)
                        ->firstOrFail();
                    $salaryRecord->update([
                        'total_minutes' => $totalMinutes,
                        'hourly_rate' => $hourlyRate,
                        'total_amount' => $salaryAmount,
                    ]);
                }
            }

            return [
                'teacher' => $teacher,
                'lessons' => $lessonCount,
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 2),
                'hourly_rate' => $hourlyRate,
                'salary_amount' => $salaryRecord->total_amount,
                'currency' => $currency,
                'status' => $salaryRecord->status,
                'record' => $salaryRecord,
            ];
        })->values();
    }

    protected function resolveMonth(string $month): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    }
}


