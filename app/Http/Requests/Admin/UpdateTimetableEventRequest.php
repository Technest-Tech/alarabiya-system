<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimetableEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'course_name' => ['required', 'string', 'max:255'],
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

            if ($endTime->lessThanOrEqualTo($startTime)) {
                $validator->errors()->add('end_time', 'End time must be after the start time.');
            }
        });
    }

    public function payload(): array
    {
        return $this->validated();
    }
}

