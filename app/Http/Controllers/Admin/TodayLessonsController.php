<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RescheduleEventRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableEvent;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TodayLessonsController extends Controller
{
    public function index(Request $request): View
    {
        $timezone = config('app.timezone');
        $now = Carbon::now($timezone);

        $filters = [
            'month' => $request->integer('month'),
            'year' => $request->integer('year'),
            'student_id' => $request->integer('student_id'),
            'teacher_id' => $request->integer('teacher_id'),
            'date' => $request->input('date'),
        ];

        // Determine the selected date
        $selectedDate = $now;
        if ($filters['date']) {
            try {
                $selectedDate = Carbon::parse($filters['date'], $timezone);
            } catch (\Exception $e) {
                $selectedDate = $now;
            }
        }

        $query = TimetableEvent::with(['student', 'teacher.user', 'timetable'])
            ->when($filters['student_id'], fn ($q) => $q->where('student_id', $filters['student_id']))
            ->when($filters['teacher_id'], fn ($q) => $q->where('teacher_id', $filters['teacher_id']));

        if ($filters['month'] && $filters['year']) {
            $startOfPeriod = Carbon::create($filters['year'], $filters['month'], 1, 0, 0, 0, $timezone)->startOfMonth();
            $endOfPeriod = $startOfPeriod->copy()->endOfMonth();
        } else {
            $startOfPeriod = $selectedDate->copy()->startOfDay();
            $endOfPeriod = $selectedDate->copy()->endOfDay();
            $filters['month'] = $selectedDate->month;
            $filters['year'] = $selectedDate->year;
        }

        $events = $query
            ->whereBetween('start_at', [$startOfPeriod->copy()->utc(), $endOfPeriod->copy()->utc()])
            ->orderBy('start_at')
            ->get();

        $events->transform(function (TimetableEvent $event) use ($timezone) {
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

        // Calculate previous and next day
        $previousDay = $selectedDate->copy()->subDay();
        $nextDay = $selectedDate->copy()->addDay();
        $isToday = $selectedDate->isToday();

        return view('admin.today-lessons.index', [
            'pageTitle' => "Today's Lessons",
            'events' => $events,
            'today' => $selectedDate->format('F d, Y'),
            'selectedDate' => $selectedDate,
            'previousDay' => $previousDay,
            'nextDay' => $nextDay,
            'isToday' => $isToday,
            'students' => Student::orderBy('name')->get(),
            'teachers' => Teacher::with('user')->get()->sortBy(fn (Teacher $teacher) => strtolower(optional($teacher->user)->name ?? '')),
            'filters' => $filters,
        ]);
    }

    public function reschedule(RescheduleEventRequest $request, TimetableEvent $event): RedirectResponse|JsonResponse
    {
        $payload = $request->validated();
        $timezone = $event->timezone ?? config('app.timezone');

        $startAtLocal = Carbon::parse(
            sprintf('%s %s', $payload['date'], $payload['start_time']),
            $timezone
        );

        $endAtLocal = Carbon::parse(
            sprintf('%s %s', $payload['date'], $payload['end_time']),
            $timezone
        );

        if ($endAtLocal->lessThanOrEqualTo($startAtLocal)) {
            $endAtLocal->addDay();
        }

        $event->update([
            'start_at' => $startAtLocal->clone()->utc(),
            'end_at' => $endAtLocal->clone()->utc(),
            'status' => 'rescheduled',
            'is_override' => true,
        ]);

        // Update student times in the timetable if needed
        $event->load('timetable');
        $timetable = $event->timetable;
        
        if ($timetable) {
            $teacherTimezone = $timetable->teacher_timezone ?? $timezone;
            $studentTimezone = $timetable->timezone;
            
            // Get the new teacher times (from the rescheduled event)
            $newTeacherStartTime = $startAtLocal->format('H:i:s');
            $newTeacherEndTime = $endAtLocal->format('H:i:s');
            
            // Get total timezone adjustment hours (sum of all adjustments)
            $timezone = $studentTimezone ?? $teacherTimezone;
            $generator = app(\App\Services\TimetableGenerator::class);
            $totalAdjustmentHours = $generator->getTotalAdjustmentHours($timezone);
            
            // For Egypt timezone, don't apply adjustment to student times
            // For other timezones, apply total adjustments to student times
            $isEgyptTimezone = $timezone === 'Africa/Cairo';
            $studentAdjustmentHours = $isEgyptTimezone ? 0 : $totalAdjustmentHours;
            
            // Calculate new student times using the generator method for consistency
            // First update the timetable's start_time and end_time temporarily to calculate correctly
            $originalStartTime = $timetable->start_time;
            $originalEndTime = $timetable->end_time;
            
            $timetable->start_time = $newTeacherStartTime;
            $timetable->end_time = $newTeacherEndTime;
            
            // Use the generator's calculateStudentTimes method
            $generator->calculateStudentTimes($timetable, $teacherTimezone, $studentTimezone, $studentAdjustmentHours);
            
            // Restore original times (they're stored in the event, not the timetable)
            $timetable->start_time = $originalStartTime;
            $timetable->end_time = $originalEndTime;
            
            // Update the timetable with new student times
            $timetable->update([
                'student_time_from' => $timetable->student_time_from,
                'student_time_to' => $timetable->student_time_to,
            ]);
        }

        $event->refresh();
        $event->load(['student', 'teacher.user', 'timetable']);
        $formattedEvent = $this->formatEvent($event);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lesson rescheduled successfully.',
                'event' => $formattedEvent,
            ]);
        }

        return redirect()->route('today-lessons.index')
            ->with('status', 'Lesson rescheduled successfully.');
    }

    public function cancel(Request $request, TimetableEvent $event): RedirectResponse|JsonResponse
    {
        $cancelType = $request->input('cancel_type', 'cancelled');
        
        $status = match($cancelType) {
            'student' => 'cancelled_student',
            'teacher' => 'cancelled_teacher',
            default => 'cancelled',
        };

        $event->update([
            'status' => $status,
        ]);

        $message = match($cancelType) {
            'student' => 'Lesson cancelled (Student).',
            'teacher' => 'Lesson cancelled (Teacher).',
            default => 'Lesson cancelled successfully.',
        };

        $event->refresh();
        $event->load(['student', 'teacher.user', 'timetable']);
        $formattedEvent = $this->formatEvent($event);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'event' => $formattedEvent,
            ]);
        }

        return redirect()->route('today-lessons.index')
            ->with('status', $message);
    }

    public function absent(Request $request, TimetableEvent $event): RedirectResponse|JsonResponse
    {
        $event->update([
            'status' => 'absent',
        ]);

        $event->refresh();
        $event->load(['student', 'teacher.user', 'timetable']);
        $formattedEvent = $this->formatEvent($event);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lesson marked as absent.',
                'event' => $formattedEvent,
            ]);
        }

        return redirect()->route('today-lessons.index')
            ->with('status', 'Lesson marked as absent.');
    }

    public function attended(Request $request, TimetableEvent $event): RedirectResponse|JsonResponse
    {
        $event->update([
            'status' => 'attended',
        ]);

        $event->refresh();
        $event->load(['student', 'teacher.user', 'timetable']);
        $formattedEvent = $this->formatEvent($event);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lesson marked as attended.',
                'event' => $formattedEvent,
            ]);
        }

        return redirect()->route('today-lessons.index')
            ->with('status', 'Lesson marked as attended.');
    }

    private function formatEvent(TimetableEvent $event): array
    {
        $timezone = config('app.timezone');
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
            'start_at' => $displayStart->format('M d, Y'),
            'start_at_day' => $displayStart->format('l'),
            'start_at_date' => $displayStart->format('Y-m-d'),
            'start_at_time' => $displayStart->format('H:i'),
            'end_at_time' => $displayEnd->format('H:i'),
            'time' => sprintf('%s – %s', $teacherStart->format('g:i A'), $teacherEnd->format('g:i A')),
            'teacher_time' => sprintf('%s – %s', $teacherStart->format('g:i A'), $teacherEnd->format('g:i A')),
            'student_time' => sprintf('%s – %s', $studentStart->format('g:i A'), $studentEnd->format('g:i A')),
            'teacher_timezone' => $teacherTimezone,
            'student_timezone' => $studentTimezone,
            'status' => $event->status ?? 'scheduled',
        ];
    }
}
