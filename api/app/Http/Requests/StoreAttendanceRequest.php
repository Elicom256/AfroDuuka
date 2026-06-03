<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function prepareForValidation()
    {
        $branchId = Auth::user()->business_branch_id;

        $this->merge([
            'business_branch_id' => $branchId,
        ]);
    }

   public function rules(): array
{
    return [
        'attendances' => 'required|array|min:1',

        'attendances.*.worker_id' => 'required|exists:workers,id',
        'attendances.*.session' => 'nullable|in:morning,afternoon,evening,night',
        'attendances.*.status' => 'required|in:present,absent,late,excused',

        'attendances.*.check_in' => 'nullable|date_format:H:i',
        'attendances.*.check_out' => 'nullable|date_format:H:i|after:attendances.*.check_in',

        'attendances.*.remarks' => 'nullable|string|max:255',
    ];
}

    public function messages(): array
    {
        return [
            'worker_id.required'        => 'Employee is required',
            'worker_id.exists'          => 'Employee must exist',
            'session.required'            => 'Session is required',
            'session.in'                  => 'Session must be morning, afternoon, or evening',
            'status.required'             => 'Status is required',
            'status.in'                   => 'Status must be present, absent, late, or excused',
            'check_in.date_format'        => 'Check‑in must be in HH:MM format',
            'check_out.date_format'       => 'Check‑out must be in HH:MM format',
            'check_out.after'             => 'Check‑out must be after check‑in',
            'remarks.max'                 => 'Remarks may not exceed 255 characters',
        ];
    }
}
