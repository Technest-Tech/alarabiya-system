<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinishTimeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supportAttendance = $this->route('supportAttendance');
        $fromTime = $supportAttendance->from_time ?? '00:00:00';

        return [
            'to_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($fromTime) {
                    $toTime = \Carbon\Carbon::createFromFormat('H:i', $value);
                    $fromTimeObj = \Carbon\Carbon::createFromFormat('H:i:s', $fromTime);
                    
                    // If end time is less than start time, assume it's next day (crosses midnight)
                    // Only reject if times are equal (same time)
                    if ($toTime->equalTo($fromTimeObj)) {
                        $fail('The to time must be different from the from time.');
                    }
                },
            ],
        ];
    }
}
