<?php

namespace App\Http\Requests\Admin;

use App\Models\TimetableEvent;
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

            // Handle times that span midnight (e.g., 23:30 to 00:30)
            // If end time is less than start time, assume it's the next day
            $endTimeForComparison = $endTime->copy();
            if ($endTimeForComparison->lessThanOrEqualTo($startTime)) {
                $endTimeForComparison->addDay();
            }

            // Check if end time equals start time (same time, not spanning midnight)
            if ($endTime->equalTo($startTime)) {
                $validator->errors()->add('end_time', 'End time must be after the start time.');
            }
            // If the duration is more than 24 hours, it's likely an error
            elseif ($endTimeForComparison->diffInHours($startTime) > 24) {
                $validator->errors()->add('end_time', 'End time must be after the start time.');
            }

            // Check for teacher time conflicts (exclude current event)
            $date = $this->input('date');
            $teacherId = $this->input('teacher_id');
            $event = $this->route('event');

            if ($date && $teacherId && $start && $end && $event) {
                try {
                    // Get the timezone from the event
                    $timezone = $event->timezone ?? config('app.timezone');
                    
                    $startAtLocal = Carbon::parse(
                        sprintf('%s %s', $date, $start),
                        $timezone
                    );
                    $endAtLocal = Carbon::parse(
                        sprintf('%s %s', $date, $end),
                        $timezone
                    );

                    // If end time is less than or equal to start time, it crosses midnight
                    if ($endAtLocal->lessThanOrEqualTo($startAtLocal)) {
                        $endAtLocal->addDay();
                    }

                    $startAt = $startAtLocal->clone()->utc();
                    $endAt = $endAtLocal->clone()->utc();

                    // Check for overlapping events (exclude current event)
                    $conflictingEvents = TimetableEvent::where('teacher_id', $teacherId)
                        ->where('id', '!=', $event->id)
                        ->where(function ($query) use ($startAt, $endAt) {
                            $query->where(function ($q) use ($startAt, $endAt) {
                                $q->where('start_at', '<=', $startAt)
                                  ->where('end_at', '>', $startAt);
                            })->orWhere(function ($q) use ($startAt, $endAt) {
                                $q->where('start_at', '<', $endAt)
                                  ->where('end_at', '>=', $endAt);
                            })->orWhere(function ($q) use ($startAt, $endAt) {
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

