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

    public function rules(): array
    {
        return [
            'session' => 'sometimes|nullable|in:morning,afternoon,evening,night',
            'status' => 'sometimes|required|in:present,absent,late,excused',

            'check_in' => 'sometimes|nullable|date_format:H:i',
            'check_out' => 'sometimes|nullable|date_format:H:i',

            'remarks' => 'sometimes|nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be present, absent, late, or excused',
            'check_in.date_format' => 'Check-in must be in HH:MM format',
            'check_out.date_format' => 'Check-out must be in HH:MM format',
            'remarks.max' => 'Remarks may not exceed 255 characters',
        ];
    }
}