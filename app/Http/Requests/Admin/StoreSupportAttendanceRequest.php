<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportAttendanceRequest extends FormRequest
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
        return [
            'date' => ['required', 'date'],
            'from_time' => ['required', 'date_format:H:i'],
            'to_time' => ['nullable', 'date_format:H:i', 'after:from_time'],
            'status' => ['required', Rule::in(['present', 'absent', 'late', 'half_day'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'support_name_id' => ['nullable', 'exists:support_names,id'],
        ];
    }
}
