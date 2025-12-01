<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTimetableEventRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimetableCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'student_id' => $request->integer('student_id'),
            'teacher_id' => $request->integer('teacher_id'),
        ];

        $students = Student::orderBy('name')->get();
        $teachers = Teacher::with('user')->get()->sortBy(function (Teacher $teacher) {
            return strtolower(optional($teacher->user)->name ?? '');
        });

        return view('admin.timetables.calendar', [
            'pageTitle' => 'Full Calendar',
            'students' => $students,
            'teachers' => $teachers,
            'filters' => $filters,
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $start = $request->query('start') ? Carbon::parse($request->query('start')) : null;
        $end = $request->query('end') ? Carbon::parse($request->query('end')) : null;
        $studentId = $request->query('student_id');
        $teacherId = $request->query('teacher_id');

        $events = TimetableEvent::with(['student', 'teacher.user', 'timetable'])
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
            ->when($teacherId, fn ($query) => $query->where('teacher_id', $teacherId))
            ->orderBy('start_at')
            ->get()
            ->map(function (TimetableEvent $event) {
                $timezone = $event->timezone ?? config('app.timezone');

                $start = $event->start_at->clone()->setTimezone($timezone);
                $end = $event->end_at->clone()->setTimezone($timezone);

                $timetable = $event->timetable;
                $studentTimezone = $timetable?->timezone;
                
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
                
                $teacherTimeRange = sprintf(
                    '%s – %s (%s)',
                    $start->format('g:i A'),
                    $end->format('g:i A'),
                    $timezone
                );

                return [
                    'id' => $event->id,
                    'title' => sprintf(
                        '%s • %s',
                        $event->student?->name ?? 'Unknown student',
                        optional($event->teacher?->user)->name ?? 'Unassigned'
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

    public function updateEvent(UpdateTimetableEventRequest $request, TimetableEvent $event): JsonResponse
    {
        $payload = $request->payload();
        $timezone = $event->timezone ?? config('app.timezone');

        $startAtLocal = Carbon::parse(
            sprintf('%s %s', $payload['date'], $payload['start_time']),
            $timezone
        );

        $endAtLocal = Carbon::parse(
            sprintf('%s %s', $payload['date'], $payload['end_time']),
            $timezone
        );

        // If end time is less than or equal to start time, it crosses midnight
        if ($endAtLocal->lessThanOrEqualTo($startAtLocal)) {
            $endAtLocal->addDay();
        }

        $startAt = $startAtLocal->clone()->utc();
        $endAt = $endAtLocal->clone()->utc();
        $studentTimezone = $event->timetable?->timezone ?? $timezone;

        $event->fill([
            'start_at' => $startAt,
            'end_at' => $endAt,
            'teacher_id' => $payload['teacher_id'],
            'course_name' => $payload['course_name'],
            'is_override' => true,
        ])->save();

        $event->load(['student', 'teacher.user', 'timetable']);

        $start = $event->start_at->clone()->setTimezone($timezone);
        $end = $event->end_at->clone()->setTimezone($timezone);
        $teacherTimeRange = sprintf(
            '%s – %s (%s)',
            $start->format('g:i A'),
            $end->format('g:i A'),
            $timezone
        );
        
        $timetable = $event->timetable;
        $studentTimezone = $timetable?->timezone;
        
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

        return response()->json([
            'message' => 'Event updated.',
            'event' => [
                'id' => $event->id,
                'title' => sprintf(
                    '%s • %s',
                    $event->student?->name ?? 'Unknown student',
                    optional($event->teacher?->user)->name ?? 'Unassigned'
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
            ],
        ]);
    }

    public function destroyEvent(TimetableEvent $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => 'Event deleted.',
        ]);
    }

    public function export(Request $request)
    {
        $studentId = $request->query('student_id');
        $teacherId = $request->query('teacher_id');
        $rangeStart = $request->query('start');
        $rangeEnd = $request->query('end');
        $preset = $request->query('preset');
        $customStart = $request->query('custom_start');
        $customEnd = $request->query('custom_end');

        if (! $rangeStart || ! $rangeEnd) {
            if ($preset) {
                [$rangeStart, $rangeEnd] = $this->resolvePresetRange($preset, $customStart, $customEnd);
            } else {
                [$rangeStart, $rangeEnd] = $this->resolvePresetRange('month', null, null);
            }
        }

        $start = Carbon::parse($rangeStart)->startOfDay()->utc();
        $end = Carbon::parse($rangeEnd)->endOfDay()->utc();

        $events = TimetableEvent::with(['student', 'teacher.user'])
            ->whereBetween('start_at', [$start, $end])
            ->when($studentId, fn ($query) => $query->where('student_id', $studentId))
            ->when($teacherId, fn ($query) => $query->where('teacher_id', $teacherId))
            ->orderBy('start_at')
            ->get();

        $data = $events->map(function (TimetableEvent $event) {
            $timezone = $event->timezone ?? config('app.timezone');
            $start = $event->start_at->clone()->setTimezone($timezone);
            $end = $event->end_at->clone()->setTimezone($timezone);

            return [
                'student' => $event->student?->name,
                'teacher' => optional($event->teacher?->user)->name,
                'course_name' => $event->course_name,
                'date' => $start->format('M d, Y'),
                'time' => sprintf('%s – %s', $start->format('g:i A'), $end->format('g:i A')),
                'timezone' => $timezone,
            ];
        });

        $pdf = Pdf::loadView('admin.timetables.export', [
            'events' => $data,
            'range' => [
                'start' => $start->clone()->setTimezone(config('app.timezone'))->format('M d, Y'),
                'end' => $end->clone()->setTimezone(config('app.timezone'))->format('M d, Y'),
            ],
            'generatedAt' => Carbon::now()->setTimezone(config('app.timezone'))->format('M d, Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('timetable-export.pdf');
    }

    private function resolvePresetRange(string $preset, ?string $customStart, ?string $customEnd): array
    {
        $now = Carbon::now();

        return match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'custom' => [
                Carbon::parse($customStart ?? $now->toDateString())->startOfDay(),
                Carbon::parse($customEnd ?? $now->toDateString())->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}

