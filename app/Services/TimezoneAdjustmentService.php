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
     * Apply timezone adjustment to all timetables in the given timezone.
     *
     * Shifts both teacher/class times (start_time, end_time, day_times) AND
     * student times (student_time_from, student_time_to) by the same number
     * of hours.
     *
     * When a country shifts its clocks (e.g. UAE +1 for DST), the real-world
     * lesson slot moves for everyone — teacher and student display times
     * both advance together.
     */
    public function applyAdjustment(string $timezone, int $adjustmentHours, int $userId): void
    {
        DB::transaction(function () use ($timezone, $adjustmentHours, $userId) {
            // Create adjustment record
            TimezoneAdjustment::create([
                'timezone'         => $timezone,
                'adjustment_hours' => $adjustmentHours,
                'applied_at'       => now(),
                'applied_by'       => $userId,
            ]);

            // Query timetables by the student timezone column
            $timetables = Timetable::where('timezone', $timezone)
                ->where('is_active', true)
                ->get();

            foreach ($timetables as $timetable) {
                // ── Teacher / class times ─────────────────────────────────────────
                // Shift the default start_time and end_time stored on the timetable.
                $startTime = Carbon::createFromFormat('H:i:s', $timetable->start_time);
                $endTime   = Carbon::createFromFormat('H:i:s', $timetable->end_time);

                $startTime->addHours($adjustmentHours);
                $endTime->addHours($adjustmentHours);

                $timetable->start_time = $startTime->format('H:i:s');
                $timetable->end_time   = $endTime->format('H:i:s');

                // Also shift per-day times (day_times JSON) if present
                $dayTimes = $timetable->day_times;
                if (!empty($dayTimes) && is_array($dayTimes)) {
                    $adjustedDayTimes = [];
                    foreach ($dayTimes as $day => $dayData) {
                        // Handle both formats: array of slots or single slot object
                        if (isset($dayData[0])) {
                            // Array of slots
                            $adjustedSlots = [];
                            foreach ($dayData as $slot) {
                                $slotStart = Carbon::createFromFormat('H:i:s', $slot['start_time']);
                                $slotEnd   = Carbon::createFromFormat('H:i:s', $slot['end_time']);
                                $slotStart->addHours($adjustmentHours);
                                $slotEnd->addHours($adjustmentHours);
                                $adjustedSlots[] = [
                                    'start_time' => $slotStart->format('H:i:s'),
                                    'end_time'   => $slotEnd->format('H:i:s'),
                                ];
                            }
                            $adjustedDayTimes[$day] = $adjustedSlots;
                        } elseif (isset($dayData['start_time'])) {
                            // Single slot object
                            $slotStart = Carbon::createFromFormat('H:i:s', $dayData['start_time']);
                            $slotEnd   = Carbon::createFromFormat('H:i:s', $dayData['end_time']);
                            $slotStart->addHours($adjustmentHours);
                            $slotEnd->addHours($adjustmentHours);
                            $adjustedDayTimes[$day] = [
                                'start_time' => $slotStart->format('H:i:s'),
                                'end_time'   => $slotEnd->format('H:i:s'),
                            ];
                        }
                    }
                    $timetable->day_times = $adjustedDayTimes;
                }

                // ── Student times ─────────────────────────────────────────────────
                // Shift student_time_from / student_time_to by the same amount so
                // both sides of the lesson reflect the real-world clock shift together.
                if ($timetable->student_time_from) {
                    $studentFrom = Carbon::createFromFormat('H:i:s', $timetable->student_time_from);
                    $studentFrom->addHours($adjustmentHours);
                    $timetable->student_time_from = $studentFrom->format('H:i:s');
                }

                if ($timetable->student_time_to) {
                    $studentTo = Carbon::createFromFormat('H:i:s', $timetable->student_time_to);
                    $studentTo->addHours($adjustmentHours);
                    $timetable->student_time_to = $studentTo->format('H:i:s');
                }

                $timetable->save();

                // Regenerate future TimetableEvents using the updated teacher times
                $this->generator->regenerate($timetable->fresh());
            }
        });
    }
}
