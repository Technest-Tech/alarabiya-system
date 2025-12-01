<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Family;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function index(): View
    {
        $families = Family::withCount('students')
            ->with(['students' => function ($query) {
                $query->select('students.id');
            }])
            ->orderBy('name')
            ->get();

        return view('admin.families.index', [
            'families' => $families,
        ]);
    }

    public function create(): View
    {
        return view('admin.families.create', [
            'students' => Student::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['exists:students,id'],
        ]);

        $family = Family::create([
            'name' => $data['name'],
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);

        if (! empty($data['student_ids'])) {
            $family->students()->attach($data['student_ids']);
        }

        return redirect()->route('admin.families.show', $family)->with('status', 'Family created successfully.');
    }

    public function edit(Family $family): View
    {
        return view('admin.families.edit', [
            'family' => $family->load('students'),
            'students' => Student::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Family $family): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['exists:students,id'],
        ]);

        $family->update([
            'name' => $data['name'],
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ]);

        $family->students()->sync($data['student_ids'] ?? []);

        return redirect()->route('admin.families.show', $family)->with('status', 'Family updated successfully.');
    }

    public function show(Request $request, Family $family): View
    {
        $timezone = config('app.timezone');
        $monthParam = $request->get('month', Carbon::now()->format('Y-m'));
        $monthDate = $this->resolveMonth($monthParam);

        $summary = $this->buildMonthlySummary($family, $monthDate);

        // Get family student IDs
        $familyStudentIds = $family->students()->pluck('students.id');

        // Filters for classes section
        $classFilters = [
            'month' => $request->integer('class_month', Carbon::now()->month),
            'year' => $request->integer('class_year', Carbon::now()->year),
            'student_id' => $request->integer('class_student_id'),
            'teacher_id' => $request->integer('class_teacher_id'),
        ];

        // Build query for timetable events
        $eventsQuery = TimetableEvent::with(['student', 'teacher.user', 'timetable'])
            ->whereIn('student_id', $familyStudentIds)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereIn('status', ['scheduled', 'rescheduled']);
            })
            ->when($classFilters['student_id'], fn ($q) => $q->where('student_id', $classFilters['student_id']))
            ->when($classFilters['teacher_id'], fn ($q) => $q->where('teacher_id', $classFilters['teacher_id']));

        // Apply date range filter
        $startOfPeriod = Carbon::create($classFilters['year'], $classFilters['month'], 1, 0, 0, 0, $timezone)->startOfMonth();
        $endOfPeriod = $startOfPeriod->copy()->endOfMonth();

        $events = $eventsQuery
            ->whereBetween('start_at', [$startOfPeriod->copy()->utc(), $endOfPeriod->copy()->utc()])
            ->orderBy('start_at')
            ->paginate(15)
            ->withQueryString();

        // Format events similar to TodayLessonsController
        $events->getCollection()->transform(function (TimetableEvent $event) use ($timezone) {
            $teacherTimezone = $event->timezone ?? $timezone;
            $timetable = $event->timetable;
            $studentTimezone = $timetable?->timezone;

            $teacherStart = $event->start_at->clone()->setTimezone($teacherTimezone);
            $teacherEnd = $event->end_at->clone()->setTimezone($teacherTimezone);
            
            // If using manual time difference and no student timezone, use stored student times
            if ($timetable && $timetable->use_manual_time_diff && !$studentTimezone && $timetable->student_time_from && $timetable->student_time_to) {
                $studentStart = Carbon::today()->setTimeFromTimeString($timetable->student_time_from);
                $studentEnd = Carbon::today()->setTimeFromTimeString($timetable->student_time_to);
                $studentTimezone = 'undefined';
            } elseif ($studentTimezone) {
                $studentStart = $event->start_at->clone()->setTimezone($studentTimezone);
                $studentEnd = $event->end_at->clone()->setTimezone($studentTimezone);
            } else {
                // Fallback to teacher timezone
                $studentStart = $teacherStart;
                $studentEnd = $teacherEnd;
                $studentTimezone = $teacherTimezone;
            }

            $displayStart = $event->start_at->clone()->setTimezone($timezone);
            $displayEnd = $event->end_at->clone()->setTimezone($timezone);

            return [
                'id' => $event->id,
                'student' => $event->student?->name,
                'teacher' => optional($event->teacher?->user)->name,
                'course_name' => $event->course_name,
                'start_at' => $displayStart,
                'end_at' => $displayEnd,
                'time' => sprintf('%s – %s', $teacherStart->format('g:i A'), $teacherEnd->format('g:i A')),
                'teacher_time' => sprintf('%s – %s', $teacherStart->format('g:i A'), $teacherEnd->format('g:i A')),
                'student_time' => sprintf('%s – %s', $studentStart->format('g:i A'), $studentEnd->format('g:i A')),
                'teacher_timezone' => $teacherTimezone,
                'student_timezone' => $studentTimezone,
                'status' => $event->status ?? 'scheduled',
            ];
        });

        // Get students and teachers for filters
        $familyStudents = $family->students()->orderBy('name')->get();
        $teachers = Teacher::with('user')->get()->sortBy(fn (Teacher $teacher) => strtolower(optional($teacher->user)->name ?? ''));

        return view('admin.families.show', [
            'family' => $family->load('students.teacher.user'),
            'month' => $monthDate->format('Y-m'),
            'monthLabel' => $monthDate->isoFormat('MMMM YYYY'),
            'summary' => $summary,
            'availableMonths' => $this->availableMonthsForFamily($family),
            'whatsappLink' => $summary['whatsapp_link'],
            'events' => $events,
            'classFilters' => $classFilters,
            'familyStudents' => $familyStudents,
            'teachers' => $teachers,
        ]);
    }

    public function destroy(Family $family): RedirectResponse
    {
        $family->students()->detach();
        $family->delete();

        return redirect()->route('admin.families.index')->with('status', 'Family deleted successfully.');
    }

    public function report(Request $request, Family $family)
    {
        $monthParam = $request->get('month', Carbon::now()->format('Y-m'));
        $monthDate = $this->resolveMonth($monthParam);

        $summary = $this->buildMonthlySummary($family, $monthDate);

        $pdf = Pdf::loadView('admin.families.report', [
            'family' => $family->load('students.teacher.user'),
            'monthLabel' => $monthDate->isoFormat('MMMM YYYY'),
            'summary' => $summary,
        ])->setPaper('a4', 'portrait');

        $fileName = sprintf(
            'family-report-%s-%s.pdf',
            Str::slug($family->name),
            $monthDate->format('Y-m')
        );

        return $pdf->download($fileName);
    }

    protected function buildMonthlySummary(Family $family, Carbon $month): array
    {
        $studentIds = $family->students()->pluck('students.id');

        if ($studentIds->isEmpty()) {
            return [
                'students' => collect(),
                'currencyTotals' => collect(),
                'familyTotal' => 0,
                'whatsapp_link' => null,
                'message' => 'No students assigned.',
            ];
        }

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $students = $family->students()->with('teacher.user')->whereIn('students.id', $studentIds)->orderBy('name')->get();

        $billings = Billing::with('items')
            ->whereIn('student_id', $studentIds)
            ->where('month', $start->toDateString())
            ->get()
            ->groupBy('student_id');

        $lessons = Lesson::with(['teacher.user', 'billingItems.billing'])
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get()
            ->groupBy('student_id');

        $studentSummaries = $students->map(function (Student $student) use ($billings, $lessons, $start) {
            /** @var Collection<int, \App\Models\Billing> $studentBillings */
            $studentBillings = $billings->get($student->id, collect());
            $automaticBilling = $studentBillings->firstWhere('type', 'automatic');
            $manualBilling = $studentBillings->firstWhere('type', 'manual');

            $billingItems = $automaticBilling ? $automaticBilling->items->keyBy('lesson_id') : collect();

            /** @var Collection<int, Lesson> $studentLessons */
            $studentLessons = $lessons->get($student->id, collect());

            $lessonRows = $studentLessons->map(function (Lesson $lesson) use ($student, $billingItems) {
                $item = $billingItems->get($lesson->id);
                $hourlyRate = $item?->hourly_rate ?? $student->hourly_rate;
                
                // Show $0 for non-calculated statuses
                $amount = $lesson->isCalculated() 
                    ? ($item?->amount ?? round(($lesson->duration_minutes / 60) * $hourlyRate, 2))
                    : 0;

                return [
                    'date' => Carbon::parse($lesson->date)->format('M d, Y'),
                    'title' => $lesson->title ?? '—',
                    'duration_minutes' => $lesson->duration_minutes,
                    'duration_label' => sprintf('%02dh %02dm', intdiv($lesson->duration_minutes, 60), $lesson->duration_minutes % 60),
                    'teacher' => optional($lesson->teacher->user)->name,
                    'hourly_rate' => $hourlyRate,
                    'amount' => $amount,
                    'status' => $lesson->status ?? 'attended',
                ];
            });

            $lessonTotal = $lessonRows->sum('amount');

            $manualEntries = $manualBilling?->items->map(function ($item) {
                return [
                    'description' => $item->description,
                    'amount' => $item->amount,
                ];
            }) ?? collect();

            $manualTotal = $manualBilling?->total_amount ?? 0;

            $currency = $automaticBilling?->currency
                ?? $manualBilling?->currency
                ?? config('app.currency', 'USD');

            $total = $lessonTotal + $manualTotal;

            return [
                'student' => $student,
                'lesson_rows' => $lessonRows,
                'lesson_total' => $lessonTotal,
                'manual_entries' => $manualEntries,
                'manual_total' => $manualTotal,
                'total' => $total,
                'currency' => $currency,
                'automatic_billing' => $automaticBilling,
                'manual_billing' => $manualBilling,
                'billings' => $studentBillings,
            ];
        });

        $currencyTotals = $studentSummaries
            ->groupBy('currency')
            ->map(fn (Collection $items) => $items->sum('total'));

        $familyTotal = $studentSummaries->sum('total');

        $messageLines = $studentSummaries->map(function ($summary) use ($start) {
            $statusParts = [];

            if ($summary['automatic_billing']) {
                $statusParts[] = sprintf('Auto: %s', ucfirst($summary['automatic_billing']->status));
            }

            if ($summary['manual_billing']) {
                $statusParts[] = sprintf('Manual: %s', ucfirst($summary['manual_billing']->status));
            }

            $statusLabel = $statusParts ? ' (' . implode(', ', $statusParts) . ')' : '';

            return sprintf(
                '%s: %s %s%s',
                $summary['student']->name,
                $summary['currency'],
                number_format($summary['total'], 2),
                $statusLabel
            );
        })->toArray();

        $totalsLines = $currencyTotals->map(function ($amount, $currency) {
            return sprintf('%s %s', $currency, number_format($amount, 2));
        })->implode(' | ');

        $message = sprintf(
            "Hello %s,\nHere is the family billing summary for %s:\n%s\nTotal: %s",
            $family->name,
            $start->isoFormat('MMMM YYYY'),
            implode("\n", $messageLines),
            $totalsLines
        );

        return [
            'students' => $studentSummaries,
            'currencyTotals' => $currencyTotals,
            'familyTotal' => $familyTotal,
            'whatsapp_link' => $family->whatsappLink($message),
            'message' => $message,
        ];
    }

    protected function availableMonthsForFamily(Family $family): Collection
    {
        $studentIds = $family->students()->pluck('students.id');

        if ($studentIds->isEmpty()) {
            return collect([Carbon::now()->format('Y-m')]);
        }

        $months = Billing::whereIn('student_id', $studentIds)
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn ($month) => Carbon::parse($month)->format('Y-m'));

        if ($months->isEmpty()) {
            $months = collect([Carbon::now()->format('Y-m')]);
        }

        return $months;
    }

    protected function resolveMonth(string $month): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    }
}


