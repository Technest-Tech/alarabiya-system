<?php

namespace App\Services;

use App\Models\Timetable;
use App\Models\TimezoneAdjustment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimetableGenerator
{
    /**
     * Mapping for normalized weekday names to Carbon constants.
     */
    protected array $weekdayMap = [
        'sunday' => Carbon::SUNDAY,
        'monday' => Carbon::MONDAY,
        'tuesday' => Carbon::TUESDAY,
        'wednesday' => Carbon::WEDNESDAY,
        'thursday' => Carbon::THURSDAY,
        'friday' => Carbon::FRIDAY,
        'saturday' => Carbon::SATURDAY,
    ];

    /**
     * Regenerate all events for the given timetable.
     * Only regenerates events from today onwards, preserving all past events.
     */
    public function regenerate(Timetable $timetable): void
    {
        // Skip inactive timetables
        if (!$timetable->is_active) {
            return;
        }

        // Check if deactivated until date has passed
        if ($timetable->deactivated_until && Carbon::now()->lessThanOrEqualTo($timetable->deactivated_until)) {
            return;
        }

        DB::transaction(function () use ($timetable): void {
            // Only delete events from today onwards (preserve past events)
            $today = Carbon::today()->startOfDay();
            $timetable->events()
                ->where('start_at', '>=', $today)
                ->delete();

            // Only generate events from today onwards
            $payloads = $this->buildEventPayloads($timetable, $today);

            if ($payloads->isEmpty()) {
                return;
            }

            $timetable->events()->createMany($payloads->all());
        });
    }

    /**
     * Build the event payloads for the timetable without persisting them.
     *
     * @param Carbon|null $minDate Minimum date to generate events from (defaults to timetable start_date)
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function buildEventPayloads(Timetable $timetable, ?Carbon $minDate = null): Collection
    {
        $startDate = $timetable->start_date instanceof Carbon
            ? $timetable->start_date->copy()
            : Carbon::parse($timetable->start_date);

        // If minDate is provided and is after startDate, use minDate instead
        if ($minDate && $minDate->gt($startDate)) {
            $startDate = $minDate->copy()->startOfDay();
        }

        $endDate = $timetable->end_date instanceof Carbon
            ? $timetable->end_date->copy()
            : Carbon::parse($timetable->end_date);

        $days = collect($timetable->days_of_week ?? [])
            ->map(fn (string $day): ?int => $this->weekdayMap[strtolower($day)] ?? null)
            ->filter(fn ($value) => $value !== null)
            ->unique()
            ->values();

        if ($startDate->gt($endDate) || $days->isEmpty()) {
            return collect();
        }

        // Use teacher timezone if available, otherwise use student timezone
        $teacherTimezone = $timetable->teacher_timezone ?? $timetable->timezone ?? config('app.timezone');
        $studentTimezone = $timetable->timezone;

        // Get total timezone adjustment hours (sum of all adjustments)
        $adjustmentHours = $this->getTotalAdjustmentHours($studentTimezone ?? $teacherTimezone);

        // For Egypt timezone (teacher_timezone), don't recalculate student times at all
        // For other timezones, apply adjustment to student times only
        $isEgyptTimezone = $teacherTimezone === 'Africa/Cairo';
        
        if (!$isEgyptTimezone) {
            // Only recalculate student times for non-Egypt timezones using total adjustments
            $this->calculateStudentTimes($timetable, $teacherTimezone, $studentTimezone, $adjustmentHours);
        }
        // For Egypt, leave student times unchanged

        // Check if using per-day times
        $dayTimes = $timetable->day_times;
        $usePerDayTimes = !empty($dayTimes) && is_array($dayTimes);

        // Map day names to normalized lowercase
        $dayNameMap = [
            'sunday' => 'sunday',
            'monday' => 'monday',
            'tuesday' => 'tuesday',
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
        ];

        $period = CarbonPeriod::create($startDate, $endDate);

        return collect($period)
            ->filter(fn (Carbon $date): bool => $days->contains($date->dayOfWeek))
            ->map(function (Carbon $date) use ($timetable, $teacherTimezone, $usePerDayTimes, $dayTimes, $dayNameMap): array {
                // Get day name in lowercase
                $dayName = strtolower($date->format('l'));
                $normalizedDayName = $dayNameMap[$dayName] ?? $dayName;

                // Determine start and end times
                if ($usePerDayTimes && isset($dayTimes[$normalizedDayName])) {
                    $dayStartTime = $dayTimes[$normalizedDayName]['start_time'] ?? $timetable->start_time;
                    $dayEndTime = $dayTimes[$normalizedDayName]['end_time'] ?? $timetable->end_time;
                } else {
                    $dayStartTime = $timetable->start_time;
                    $dayEndTime = $timetable->end_time;
                }

                $endDateInstance = $date->copy();
                if (Carbon::createFromFormat('H:i:s', $dayEndTime)->lessThanOrEqualTo(Carbon::createFromFormat('H:i:s', $dayStartTime))) {
                    $endDateInstance = $endDateInstance->addDay();
                }

                // Use teacher timezone for event times
                $startAt = Carbon::parse(
                    sprintf('%s %s', $date->toDateString(), $dayStartTime),
                    $teacherTimezone
                )->utc();

                $endAt = Carbon::parse(
                    sprintf('%s %s', $endDateInstance->toDateString(), $dayEndTime),
                    $teacherTimezone
                )->utc();

                return [
                    'student_id' => $timetable->student_id,
                    'teacher_id' => $timetable->teacher_id,
                    'course_name' => $timetable->course_name,
                    'timezone' => $teacherTimezone,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'is_override' => false,
                    'status' => 'scheduled',
                    'metadata' => null,
                ];
            });
    }

    /**
     * Calculate student times based on timezone difference or manual offset.
     * Always calculates from original class times + timezone diff + total adjustments.
     */
    public function calculateStudentTimes(Timetable $timetable, string $teacherTimezone, ?string $studentTimezone, int $adjustmentHours = 0): void
    {
        // Check if using per-day times - if so, use first day's times for student time calculation
        $dayTimes = $timetable->day_times;
        $usePerDayTimes = !empty($dayTimes) && is_array($dayTimes);

        if ($usePerDayTimes && !empty($dayTimes)) {
            // Use first available day's times for student time calculation
            $firstDay = array_key_first($dayTimes);
            $startTimeStr = $dayTimes[$firstDay]['start_time'] ?? $timetable->start_time;
            $endTimeStr = $dayTimes[$firstDay]['end_time'] ?? $timetable->end_time;
        } else {
            $startTimeStr = $timetable->start_time;
            $endTimeStr = $timetable->end_time;
        }

        // Always use original class times as the base
        // Create Carbon instances with today's date to properly handle day rollover
        $startTime = Carbon::today()->setTimeFromTimeString($startTimeStr);
        $endTime = Carbon::today()->setTimeFromTimeString($endTimeStr);

        // Check if using manual time difference (explicitly check for true, not just truthy)
        if ($timetable->use_manual_time_diff === true && $timetable->time_difference_hours !== null) {
            // Use manual time difference + total adjustments
            $totalDiff = (int) $timetable->time_difference_hours + $adjustmentHours;
            $studentStartTime = $startTime->copy()->addHours($totalDiff);
            $studentEndTime = $endTime->copy()->addHours($totalDiff);
        } elseif ($studentTimezone && $teacherTimezone) {
            // Calculate based on timezone difference
            $teacherOffset = Carbon::now($teacherTimezone)->utcOffset();
            $studentOffset = Carbon::now($studentTimezone)->utcOffset();
            $diffMinutes = $studentOffset - $teacherOffset;

            // Apply total timezone adjustments
            $totalMinutes = (int) round($diffMinutes + ($adjustmentHours * 60));

            $studentStartTime = $startTime->copy()->addMinutes($totalMinutes);
            $studentEndTime = $endTime->copy()->addMinutes($totalMinutes);

            // Store the base timezone difference (without adjustments)
            $timetable->time_difference_hours = (int) round($diffMinutes / 60);
        } else {
            // No student timezone, use teacher timezone only
            $studentStartTime = $startTime;
            $studentEndTime = $endTime;
        }

        // Format times - Carbon automatically handles day rollover (e.g., 23:00 + 2 hours = 01:00 next day)
        // The H:i:s format will show 00:00:00 for midnight, which is correct
        // Use format('H:i:s') to get 24-hour format time string
        $timetable->student_time_from = $studentStartTime->format('H:i:s');
        $timetable->student_time_to = $studentEndTime->format('H:i:s');
    }

    /**
     * Get the latest timezone adjustment for a given timezone.
     */
    protected function getTimezoneAdjustment(string $timezone): ?TimezoneAdjustment
    {
        return TimezoneAdjustment::where('timezone', $timezone)
            ->latest('applied_at')
            ->first();
    }

    /**
     * Get the total adjustment hours for a given timezone (sum of all adjustments).
     */
    public function getTotalAdjustmentHours(string $timezone): int
    {
        return (int) TimezoneAdjustment::where('timezone', $timezone)
            ->sum('adjustment_hours');
    }
}

