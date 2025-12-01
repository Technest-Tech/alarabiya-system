<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $timezones = array_keys(config('timetables.timezones'));
        $daysOfWeek = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        return [
            'student_id' => ['required', 'exists:students,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'course_name' => ['required', 'string', 'max:255'],
            'timezone' => ['nullable', Rule::in($timezones)],
            'teacher_timezone' => ['nullable', Rule::in($timezones)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['string', Rule::in($daysOfWeek)],
            'time_difference_hours' => ['nullable', 'integer', 'min:-12', 'max:12'],
            'use_manual_time_diff' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if (! $start || ! $end) {
                return;
            }

            try {
                $startTime = Carbon::createFromFormat('H:i', $start);
                $endTime = Carbon::createFromFormat('H:i', $end);
            } catch (\InvalidArgumentException) {
                return;
            }

            if ($endTime->equalTo($startTime)) {
                $validator->errors()->add('end_time', 'End time must be after the start time.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->days_of_week)) {
            $this->merge([
                'days_of_week' => array_values($this->days_of_week),
            ]);
        }

        if ($this->has('use_manual_time_diff')) {
            $this->merge([
                'use_manual_time_diff' => filter_var(
                    $this->input('use_manual_time_diff'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ),
            ]);
        }
    }

    public function payload(): array
    {
        $data = $this->validated();

        $data['start_time'] = Carbon::createFromFormat('H:i', $data['start_time'])->format('H:i:s');
        $data['end_time'] = Carbon::createFromFormat('H:i', $data['end_time'])->format('H:i:s');
        $data['days_of_week'] = array_values($data['days_of_week']);
        $data['is_active'] = true;

        // Ensure time_difference_hours is included (even if null)
        if (!isset($data['time_difference_hours'])) {
            $data['time_difference_hours'] = $this->input('time_difference_hours');
        }

        // Ensure teacher_timezone is set
        if (empty($data['teacher_timezone'])) {
            $data['teacher_timezone'] = $data['timezone'] ?? config('app.timezone');
        }
        
        $teacherTimezone = $data['teacher_timezone'] ?? config('app.timezone');

        // Auto-detect manual time difference: if timezone is empty and time_difference_hours is set
        $hasNoTimezone = empty($data['timezone']);
        $hasManualDiff = isset($data['time_difference_hours']) && $data['time_difference_hours'] !== null && $data['time_difference_hours'] !== '';
        
        if ($hasNoTimezone && $hasManualDiff) {
            $data['use_manual_time_diff'] = true;
        } else {
            $data['use_manual_time_diff'] = $this->boolean('use_manual_time_diff', false);
        }

        if ($data['use_manual_time_diff']) {
            // When using manual time difference and no student timezone is provided,
            // set timezone to null (will display as "undefined")
            // The student times will be calculated and stored in student_time_from/student_time_to
            if (empty($data['timezone'])) {
                $data['timezone'] = null;
            }
        } elseif (empty($data['timezone'])) {
            $data['timezone'] = $teacherTimezone;
        }

        return $data;
    }

    /**
     * Calculate a valid timezone identifier for the student based on teacher timezone + offset.
     * Returns a timezone that currently matches the calculated UTC offset.
     */
    protected function calculateStudentTimezone(string $teacherTimezone, int $diffHours): string
    {
        $teacherOffsetMinutes = Carbon::now($teacherTimezone)->utcOffset();
        $studentOffsetMinutes = $teacherOffsetMinutes + ($diffHours * 60);
        $targetOffsetMinutes = (int) round($studentOffsetMinutes);

        // List of common timezones to check
        $timezonesToCheck = [
            'UTC', 'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Europe/Rome', 'Europe/Madrid',
            'Europe/Moscow', 'Asia/Dubai', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Dhaka',
            'Asia/Bangkok', 'Asia/Singapore', 'Asia/Shanghai', 'Asia/Tokyo', 'Asia/Seoul',
            'Australia/Sydney', 'Australia/Melbourne', 'Pacific/Auckland',
            'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
            'America/Mexico_City', 'America/Sao_Paulo', 'America/Argentina/Buenos_Aires',
            'Africa/Cairo', 'Africa/Johannesburg', 'Africa/Lagos',
        ];

        // Find a timezone that currently matches the target offset
        foreach ($timezonesToCheck as $tz) {
            try {
                $tzOffset = Carbon::now($tz)->utcOffset();
                if ($tzOffset === $targetOffsetMinutes) {
                    return $tz;
                }
            } catch (\Exception $e) {
                // Skip invalid timezones
                continue;
            }
        }

        // If no exact match, use a timezone based on the offset hours
        $studentOffsetHours = (int) round($targetOffsetMinutes / 60);
        $studentOffsetHours = max(-12, min(12, $studentOffsetHours));

        $offsetToTimezone = [
            -12 => 'Pacific/Baker_Island',
            -11 => 'Pacific/Midway',
            -10 => 'Pacific/Honolulu',
            -9 => 'America/Anchorage',
            -8 => 'America/Los_Angeles',
            -7 => 'America/Denver',
            -6 => 'America/Chicago',
            -5 => 'America/New_York',
            -4 => 'America/Caracas',
            -3 => 'America/Sao_Paulo',
            -2 => 'Atlantic/South_Georgia',
            -1 => 'Atlantic/Azores',
            0 => 'UTC',
            1 => 'Europe/Paris',
            2 => 'Europe/Berlin',
            3 => 'Europe/Moscow',
            4 => 'Asia/Dubai',
            5 => 'Asia/Karachi',
            6 => 'Asia/Dhaka',
            7 => 'Asia/Bangkok',
            8 => 'Asia/Shanghai',
            9 => 'Asia/Tokyo',
            10 => 'Australia/Sydney',
            11 => 'Pacific/Norfolk',
            12 => 'Pacific/Auckland',
        ];

        return $offsetToTimezone[$studentOffsetHours] ?? 'UTC';
    }
}

