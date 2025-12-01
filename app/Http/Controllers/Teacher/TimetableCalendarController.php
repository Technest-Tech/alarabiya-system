<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TimetableEvent;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimetableCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = Auth::user()->teacher;
        
        if (!$teacher) {
            abort(403, 'Teacher profile not found.');
        }

        $filters = [
            'student_id' => $request->integer('student_id'),
        ];

        // Get students assigned to this teacher
        $students = Student::where('assigned_teacher_id', $teacher->id)
            ->orderBy('name')
            ->get();

        return view('teacher.timetables.calendar', [
            'pageTitle' => 'My Calendar',
            'students' => $students,
            'filters' => $filters,
            'teacherId' => $teacher->id,
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $teacher = Auth::user()->teacher;
        
        if (!$teacher) {
            return response()->json([]);
        }

        $start = $request->query('start') ? Carbon::parse($request->query('start')) : null;
        $end = $request->query('end') ? Carbon::parse($request->query('end')) : null;
        $studentId = $request->query('student_id');

        $events = TimetableEvent::with(['student', 'teacher.user', 'timetable'])
            ->where('teacher_id', $teacher->id)
            ->whereHas('timetable', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('deactivated_until')
                            ->orWhere('deactivated_until', '<', now());
                    });
            })
            ->when($start, fn ($query) => $query->where('start_at', '>=', $start->clone()->utc()))
            ->when($end, fn ($query) => $query->where('end_at', '<=', $end->clone()->utc()))
            ->when($studentId, fn ($query) => $query->where('student_id', $studentId))
            ->orderBy('start_at')
            ->get()
            ->map(function (TimetableEvent $event) {
                $timezone = $event->timezone ?? config('app.timezone');
                $timetable = $event->timetable;
                $studentTimezone = $timetable?->timezone;

                $start = $event->start_at->clone()->setTimezone($timezone);
                $end = $event->end_at->clone()->setTimezone($timezone);
                $teacherTimeRange = sprintf(
                    '%s – %s (%s)',
                    $start->format('g:i A'),
                    $end->format('g:i A'),
                    $timezone
                );
                
                // If using manual time difference and no student timezone, use stored student times
                if ($timetable && $timetable->use_manual_time_diff && !$studentTimezone && $timetable->student_time_from && $timetable->student_time_to) {
                    $studentTimeRange = sprintf(
                        '%s – %s (undefined)',
                        Carbon::today()->setTimeFromTimeString($timetable->student_time_from)->format('g:i A'),
                        Carbon::today()->setTimeFromTimeString($timetable->student_time_to)->format('g:i A')
                    );
                } elseif ($timetable && $timetable->student_time_from && $timetable->student_time_to && $studentTimezone) {
                    // Use stored student times (which include timezone adjustments) when available
                    $studentTimeRange = sprintf(
                        '%s – %s (%s)',
                        Carbon::today()->setTimeFromTimeString($timetable->student_time_from)->format('g:i A'),
                        Carbon::today()->setTimeFromTimeString($timetable->student_time_to)->format('g:i A'),
                        $studentTimezone
                    );
                } elseif ($studentTimezone) {
                    // Fallback: calculate from event times (apply total adjustments if exists)
                    $generator = app(\App\Services\TimetableGenerator::class);
                    $totalAdjustmentHours = $generator->getTotalAdjustmentHours($studentTimezone);
                    
                    // For Egypt timezone, don't apply adjustment to student times
                    // For other timezones, apply total adjustments to student times
                    $isEgyptTimezone = $studentTimezone === 'Africa/Cairo';
                    $studentAdjustmentHours = $isEgyptTimezone ? 0 : $totalAdjustmentHours;
                    
                    $studentStart = $event->start_at->clone()->setTimezone($studentTimezone);
                    $studentEnd = $event->end_at->clone()->setTimezone($studentTimezone);
                    
                    if ($studentAdjustmentHours != 0) {
                        $studentStart->addHours($studentAdjustmentHours);
                        $studentEnd->addHours($studentAdjustmentHours);
                    }
                    
                    $studentTimeRange = sprintf(
                        '%s – %s (%s)',
                        $studentStart->format('g:i A'),
                        $studentEnd->format('g:i A'),
                        $studentTimezone
                    );
                } else {
                    // Fallback to teacher timezone
                    $studentTimeRange = sprintf(
                        '%s – %s (%s)',
                        $start->format('g:i A'),
                        $end->format('g:i A'),
                        $timezone
                    );
                }

                return [
                    'id' => $event->id,
                    'title' => sprintf(
                        '%s • %s',
                        $event->student?->name ?? 'Unknown student',
                        $event->course_name ?? 'No course'
                    ),
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                    'extendedProps' => [
                        'student' => $event->student?->name,
                        'teacher' => optional($event->teacher?->user)->name,
                        'teacher_id' => $event->teacher_id,
                        'course_name' => $event->course_name,
                        'timezone' => $timezone,
                        'displayTime' => $teacherTimeRange,
                        'student_timezone' => $studentTimezone,
                        'student_time_display' => $studentTimeRange,
                        'timetable_id' => $event->timetable_id,
                        'is_override' => (bool) $event->is_override,
                        'duration' => $start->diffInMinutes($end),
                    ],
                ];
            });

        return response()->json($events);
    }
}

