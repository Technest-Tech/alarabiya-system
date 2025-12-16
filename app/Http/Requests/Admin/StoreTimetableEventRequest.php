<?php

namespace App\Http\Requests\Admin;

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
        });
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
