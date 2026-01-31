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
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->clone()->endOfMonth();

        $lessons = Lesson::with(['teacher.user', 'billingItems.billing'])
            ->where('student_id', $student->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $automaticBilling = Billing::where('student_id', $student->id)
            ->where('month', $start->toDateString())
            ->where('type', 'automatic')
            ->with('items')
            ->first();

        $lessonRows = $lessons->map(function (Lesson $lesson) use ($student, $start) {
            $billingItem = $lesson->billingItems
                ->first(function ($item) use ($start) {
                    return $item->billing && $item->billing->month->equalTo($start) && $item->billing->type === 'automatic';
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

        $manualBilling = Billing::with('items')
            ->where('student_id', $student->id)
            ->where('month', $start->toDateString())
            ->where('type', 'manual')
            ->first();

        $manualEntries = $manualBilling?->items->map(function ($item) {
            return [
                'description' => $item->description,
                'amount' => $item->amount,
            ];
        }) ?? collect();

        $manualTotal = $manualBilling?->total_amount ?? 0;

        // Use student's currency as primary source, fallback to billing currency, then config default
        $currency = $student->currency
            ?? $automaticBilling?->currency
            ?? $manualBilling?->currency
            ?? config('app.currency', 'USD');

        $pdf = Pdf::loadView('admin.packages.report', [
            'student' => $student,
            'monthLabel' => $start->isoFormat('MMMM YYYY'),
            'lessonRows' => $lessonRows,
            'lessonTotal' => $lessonTotal,
            'manualEntries' => $manualEntries,
            'manualTotal' => $manualTotal,
            'grandTotal' => $lessonTotal + $manualTotal,
            'currency' => $currency,
        ])->setPaper('a4', 'portrait');

        $fileName = sprintf(
            'student-report-%s-%s.pdf',
            Str::slug($student->name),
            $start->format('Y-m')
        );

        return $pdf->download($fileName);
    }
}
