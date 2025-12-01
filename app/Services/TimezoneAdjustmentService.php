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
     */
    public function applyAdjustment(string $timezone, int $adjustmentHours, int $userId): void
    {
        DB::transaction(function () use ($timezone, $adjustmentHours, $userId) {
            // Create adjustment record
            TimezoneAdjustment::create([
                'timezone' => $timezone,
                'adjustment_hours' => $adjustmentHours,
                'applied_at' => now(),
                'applied_by' => $userId,
            ]);

            $isEgyptTimezone = $timezone === 'Africa/Cairo';

            // For Egypt: query timetables where teacher_timezone is Egypt (since we adjust class times)
            // For other timezones: query timetables where student timezone matches
            if ($isEgyptTimezone) {
                $timetables = Timetable::where('teacher_timezone', $timezone)
                    ->where('is_active', true)
                    ->get();
            } else {
                $timetables = Timetable::where('timezone', $timezone)
                    ->where('is_active', true)
                    ->get();
            }

            foreach ($timetables as $timetable) {
                $teacherTimezone = $timetable->teacher_timezone ?? $timetable->timezone ?? config('app.timezone');
                $studentTimezone = $timetable->timezone;

                if ($isEgyptTimezone) {
                    // For Egypt timezone: adjust class/teacher times only, DON'T update student times at all
                    $startTime = Carbon::createFromFormat('H:i:s', $timetable->start_time);
                    $endTime = Carbon::createFromFormat('H:i:s', $timetable->end_time);
                    
                    $startTime->addHours($adjustmentHours);
                    $endTime->addHours($adjustmentHours);
                    
                    $timetable->start_time = $startTime->format('H:i:s');
                    $timetable->end_time = $endTime->format('H:i:s');
                    
                    // Do NOT recalculate student times - leave them unchanged
                } else {
                    // For non-Egypt timezones: adjust student times only, NOT class/teacher times
                    // Recalculate student times using TOTAL of all adjustments (not just the new one)
                    $totalAdjustmentHours = $this->generator->getTotalAdjustmentHours($timezone);
                    $this->generator->calculateStudentTimes($timetable, $teacherTimezone, $studentTimezone, $totalAdjustmentHours);
                }
                
                $timetable->save();

                // Regenerate events with new adjustment
                $this->generator->regenerate($timetable->fresh());
            }
        });
    }
}


