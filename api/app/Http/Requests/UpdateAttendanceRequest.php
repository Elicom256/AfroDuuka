<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

     public function prepareForValidation()
    {

        // $this->input("check_out", null);
    }

    public function rules(): array
    {
        return [
            'session' => 'sometimes|nullable|in:morning,afternoon,evening,night',
            'status' => 'sometimes|required|in:present,absent,late,excused',

            'check_in' => 'sometimes|nullable|date',
            'check_out' => 'sometimes|nullable|date|after_or_equal:check_in',

            'remarks' => 'sometimes|nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be present, absent, late, or excused',
            // 'check_in.date_format' => 'Check-in must be in HH:MM format',
            'check_out.after_or_equal' => 'Check-out must be after or equal to check-in',
            'remarks.max' => 'Remarks may not exceed 255 characters',
        ];
    }
}