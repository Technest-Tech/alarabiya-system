<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = Auth::user()->teacher;

        $monthStart = Carbon::now()->startOfMonth()->toDateString();

        // This month's rewards & deductions for this teacher.
        $currentAdjustments = TeacherAdjustment::where('teacher_id', $teacher->id)
            ->whereDate('month', $monthStart)
            ->latest()
            ->get();

        $currentRewards = round((float) $currentAdjustments->where('type', 'reward')->sum('amount'), 2);
        $currentDeductions = round((float) $currentAdjustments->where('type', 'deduction')->sum('amount'), 2);

        // A little history: the most recent adjustments across all months.
        $recentAdjustments = TeacherAdjustment::where('teacher_id', $teacher->id)
            ->orderByDesc('month')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('teacher.dashboard', [
            'currentAdjustments' => $currentAdjustments,
            'currentRewards' => $currentRewards,
            'currentDeductions' => $currentDeductions,
            'currentNet' => round($currentRewards - $currentDeductions, 2),
            'recentAdjustments' => $recentAdjustments,
            'teacherCurrency' => $teacher->currency ?? 'EGP',
            'currentMonthLabel' => Carbon::now()->isoFormat('MMMM YYYY'),
        ]);
    }
}
