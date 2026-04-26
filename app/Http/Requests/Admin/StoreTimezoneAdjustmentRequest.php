<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimezoneAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $timezones = array_keys(config('timetables.timezones'));

        return [
            'timezone'         => ['required', Rule::in($timezones)],
            'adjustment_hours' => ['required', 'integer', Rule::in([-1, 1])],
            'target'           => ['required', Rule::in(['student', 'teacher'])],
        ];
    }
}
