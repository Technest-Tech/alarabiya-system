<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Lesson;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $search = $request->get('search', '');
        
        $query = Student::with(['teacher.user', 'currentPackage', 'packages' => function($query) {
            $query->whereIn('status', ['completed', 'paid']);
        }]);
        
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        $students = $query->orderBy('name')->get();
        return view('admin.packages.index', compact('students','month','year','search'));
    }

    public function completedPackages(Student $student)
    {
        $packages = $student->packages()
            ->whereIn('status', ['completed', 'paid'])
            ->with(['lessons.teacher.user'])
            ->orderBy('completed_at', 'desc')
            ->orderBy('paid_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        
        return view('admin.packages.completed', compact('student', 'packages'));
    }

    public function studentReport(Request $request, Student $student)
    {
        // Validate date inputs
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $start = Carbon::parse($request->get('from_date'))->startOfDay();
        $end = Carbon::parse($request->get('to_date'))->endOfDay();

        $lessons = Lesson::with(['teacher.user', 'billingItems.billing'])
            ->where('student_id', $student->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        // Get automatic billings within the date range
        $automaticBillings = Billing::where('student_id', $student->id)
            ->where('type', 'automatic')
            ->whereBetween('month', [$start->startOfMonth()->toDateString(), $end->endOfMonth()->toDateString()])
            ->with('items')
            ->get();

        $lessonRows = $lessons->map(function (Lesson $lesson) use ($student) {
            $billingItem = $lesson->billingItems
                ->first(function ($item) {
                    return $item->billing 
                        && $item->billing->type === 'automatic';
                });

            $hourlyRate = $billingItem?->hourly_rate ?? $student->hourly_rate;
            
            // Show $0 for non-calculated statuses
            $amount = $lesson->isCalculated() 
                ? ($billingItem?->amount ?? round(($lesson->duration_minutes / 60) * $hourlyRate, 2))
                : 0;

            return [
                'date' => Carbon::parse($lesson->date)->format('M d, Y'),
                'duration_minutes' => $lesson->duration_minutes,
                'duration_label' => sprintf('%02dh %02dm', intdiv($lesson->duration_minutes, 60), $lesson->duration_minutes % 60),
                'teacher' => optional($lesson->teacher->user)->name,
                'hourly_rate' => $hourlyRate,
                'amount' => $amount,
                'status' => $lesson->status ?? 'attended',
            ];
        });

        $lessonTotal = $lessonRows->sum('amount');

        // Get manual billings within the date range
        $manualBillings = Billing::with('items')
            ->where('student_id', $student->id)
            ->where('type', 'manual')
            ->whereBetween('month', [$start->startOfMonth()->toDateString(), $end->endOfMonth()->toDateString()])
            ->get();

        // Collect manual entries from all manual billings in the range
        $manualEntries = collect();
        foreach ($manualBillings as $manualBilling) {
            foreach ($manualBilling->items as $item) {
                $manualEntries->push([
                    'description' => $item->description,
                    'amount' => $item->amount,
                ]);
            }
        }

        $manualTotal = $manualBillings->sum('total_amount');

        // Use student's currency as primary source, fallback to billing currency, then config default
        $firstAutomaticBilling = $automaticBillings->first();
        $firstManualBilling = $manualBillings->first();
        $currency = $student->currency
            ?? $firstAutomaticBilling?->currency
            ?? $firstManualBilling?->currency
            ?? config('app.currency', 'USD');

        $dateRangeLabel = $start->format('M d, Y') . ' - ' . $end->format('M d, Y');

        $pdf = Pdf::loadView('admin.packages.report', [
            'student' => $student,
            'monthLabel' => $dateRangeLabel,
            'lessonRows' => $lessonRows,
            'lessonTotal' => $lessonTotal,
            'manualEntries' => $manualEntries,
            'manualTotal' => $manualTotal,
            'grandTotal' => $lessonTotal + $manualTotal,
            'currency' => $currency,
        ])->setPaper('a4', 'portrait');

        $fileName = sprintf(
            'student-report-%s-%s-to-%s.pdf',
            Str::slug($student->name),
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );

        return $pdf->download($fileName);
    }
}
