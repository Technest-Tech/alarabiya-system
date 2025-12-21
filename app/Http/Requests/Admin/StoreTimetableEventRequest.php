<?php

namespace App\Http\Requests\Admin;

use App\Models\TimetableEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimetableEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'teacher_timezone' => ['required', 'string'],
            'student_timezone' => ['nullable', 'string'],
            'teacher_start_time' => ['required', 'date_format:H:i'],
            'teacher_end_time' => ['required', 'date_format:H:i'],
            'student_start_time' => ['nullable', 'date_format:H:i'],
            'student_end_time' => ['nullable', 'date_format:H:i'],
            'use_manual_time_diff' => ['nullable'],
            'student_id' => ['required', 'exists:students,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'course_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $teacherStart = $this->input('teacher_start_time');
            $teacherEnd = $this->input('teacher_end_time');
            $studentStart = $this->input('student_start_time');
            $studentEnd = $this->input('student_end_time');
            $useManual = filter_var($this->input('use_manual_time_diff'), FILTER_VALIDATE_BOOLEAN);
            $date = $this->input('date');
            $teacherId = $this->input('teacher_id');
            $teacherTimezone = $this->input('teacher_timezone');

            // Validate teacher times
            if ($teacherStart && $teacherEnd) {
                try {
                    $startTime = Carbon::createFromFormat('H:i', $teacherStart);
                    $endTime = Carbon::createFromFormat('H:i', $teacherEnd);
                    if ($endTime->lessThanOrEqualTo($startTime)) {
                        $validator->errors()->add('teacher_end_time', 'Teacher end time must be after the start time.');
                    }
                } catch (\InvalidArgumentException) {
                    // Skip if invalid format
                }
            }

            // Validate student times if manual time difference is used
            if ($useManual && $studentStart && $studentEnd) {
                try {
                    $startTime = Carbon::createFromFormat('H:i', $studentStart);
                    $endTime = Carbon::createFromFormat('H:i', $studentEnd);
                    if ($endTime->lessThanOrEqualTo($startTime)) {
                        $validator->errors()->add('student_end_time', 'Student end time must be after the start time.');
                    }
                } catch (\InvalidArgumentException) {
                    // Skip if invalid format
                }
            }

            // Check for teacher time conflicts
            if ($date && $teacherId && $teacherStart && $teacherEnd && $teacherTimezone) {
                try {
                    $startAtLocal = Carbon::parse(
                        sprintf('%s %s', $date, $teacherStart),
                        $teacherTimezone
                    );
                    $endAtLocal = Carbon::parse(
                        sprintf('%s %s', $date, $teacherEnd),
                        $teacherTimezone
                    );

                    // If end time is less than or equal to start time, it crosses midnight
                    if ($endAtLocal->lessThanOrEqualTo($startAtLocal)) {
                        $endAtLocal->addDay();
                    }

                    $startAt = $startAtLocal->clone()->utc();
                    $endAt = $endAtLocal->clone()->utc();

                    // Check for overlapping events for the same teacher
                    $conflictingEvents = \App\Models\TimetableEvent::where('teacher_id', $teacherId)
                        ->where(function ($query) use ($startAt, $endAt) {
                            $query->where(function ($q) use ($startAt, $endAt) {
                                // New event starts during existing event
                                $q->where('start_at', '<=', $startAt)
                                  ->where('end_at', '>', $startAt);
                            })->orWhere(function ($q) use ($startAt, $endAt) {
                                // New event ends during existing event
                                $q->where('start_at', '<', $endAt)
                                  ->where('end_at', '>=', $endAt);
                            })->orWhere(function ($q) use ($startAt, $endAt) {
                                // New event completely contains existing event
                                $q->where('start_at', '>=', $startAt)
                                  ->where('end_at', '<=', $endAt);
                            });
                        })
                        ->exists();

                    if ($conflictingEvents) {
                        $validator->errors()->add('teacher_id', 'This teacher already has a lesson scheduled at this time. Please choose a different time.');
                    }
                } catch (\Exception $e) {
                    // Skip conflict check if date/time parsing fails
                }
            }
        });
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
