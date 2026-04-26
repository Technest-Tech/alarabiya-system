<?php

namespace App\Services;

use App\Models\Timetable;
use App\Models\TimezoneAdjustment;
use App\Services\TimetableGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TimezoneAdjustmentService
{
    public function __construct(
        private readonly TimetableGenerator $generator
    ) {
    }

    /**
     * Apply timezone adjustment to timetables.
     *
     * @param string $timezone  IANA timezone identifier (e.g. 'Asia/Dubai')
     * @param int    $adjustmentHours  +1 or -1
     * @param int    $userId    ID of the admin applying the change
     * @param string $target    'student' – shifts student_time_from/student_time_to
     *                          'teacher' – shifts start_time/end_time/day_times & regenerates events
     */
    public function applyAdjustment(string $timezone, int $adjustmentHours, int $userId, string $target = 'student'): void
    {
        DB::transaction(function () use ($timezone, $adjustmentHours, $userId, $target) {
            // Record the adjustment
            TimezoneAdjustment::create([
                'timezone'         => $timezone,
                'target'           => $target,
                'adjustment_hours' => $adjustmentHours,
                'applied_at'       => now(),
                'applied_by'       => $userId,
            ]);

            if ($target === 'teacher') {
                $this->applyTeacherAdjustment($timezone, $adjustmentHours);
            } else {
                $this->applyStudentAdjustment($timezone, $adjustmentHours);
            }
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Shift teacher-side times (start_time, end_time, day_times) for all
     * timetables whose teacher_timezone matches, then regenerate future events.
     */
    private function applyTeacherAdjustment(string $timezone, int $hours): void
    {
        $timetables = Timetable::where('teacher_timezone', $timezone)
            ->where('is_active', true)
            ->get();

        foreach ($timetables as $timetable) {
            // Default start / end
            $startTime = Carbon::createFromFormat('H:i:s', $timetable->start_time);
            $endTime   = Carbon::createFromFormat('H:i:s', $timetable->end_time);
            $startTime->addHours($hours);
            $endTime->addHours($hours);
            $timetable->start_time = $startTime->format('H:i:s');
            $timetable->end_time   = $endTime->format('H:i:s');

            // Per-day slots
            $dayTimes = $timetable->day_times;
            if (!empty($dayTimes) && is_array($dayTimes)) {
                $adjusted = [];
                foreach ($dayTimes as $day => $dayData) {
                    if (isset($dayData[0])) {
                        $slots = [];
                        foreach ($dayData as $slot) {
                            $s = Carbon::createFromFormat('H:i:s', $slot['start_time'])->addHours($hours);
                            $e = Carbon::createFromFormat('H:i:s', $slot['end_time'])->addHours($hours);
                            $slots[] = ['start_time' => $s->format('H:i:s'), 'end_time' => $e->format('H:i:s')];
                        }
                        $adjusted[$day] = $slots;
                    } elseif (isset($dayData['start_time'])) {
                        $s = Carbon::createFromFormat('H:i:s', $dayData['start_time'])->addHours($hours);
                        $e = Carbon::createFromFormat('H:i:s', $dayData['end_time'])->addHours($hours);
                        $adjusted[$day] = ['start_time' => $s->format('H:i:s'), 'end_time' => $e->format('H:i:s')];
                    }
                }
                $timetable->day_times = $adjusted;
            }

            $timetable->save();

            // Regenerate future TimetableEvents from the updated teacher times
            $this->generator->regenerate($timetable->fresh());
        }
    }

    /**
     * Shift student-side display times (student_time_from, student_time_to) for
     * all timetables whose timezone (= student timezone) matches.
     * Teacher times and events are NOT touched.
     */
    private function applyStudentAdjustment(string $timezone, int $hours): void
    {
        $timetables = Timetable::where('timezone', $timezone)
            ->where('is_active', true)
            ->get();

        foreach ($timetables as $timetable) {
            if ($timetable->student_time_from) {
                $from = Carbon::createFromFormat('H:i:s', $timetable->student_time_from)->addHours($hours);
                $timetable->student_time_from = $from->format('H:i:s');
            }

            if ($timetable->student_time_to) {
                $to = Carbon::createFromFormat('H:i:s', $timetable->student_time_to)->addHours($hours);
                $timetable->student_time_to = $to->format('H:i:s');
            }

            $timetable->save();
            // No event regeneration needed — events are teacher-time-based
        }
    }
}
