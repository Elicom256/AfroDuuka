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
            // 🔎 Core fields
            'employee_id'        => 'required|exists:users,id',
            'business_branch_id' => 'required|exists:business_branches,id',
            'date'               => 'required|date_format:m/d/Y',
            'session'            => 'required|in:morning,afternoon,evening',
            'status'             => 'required|in:present,absent,late,excused',

            // ⏱ Optional time tracking
            'check_in'           => 'nullable|date_format:H:i',
            'check_out'          => 'nullable|date_format:H:i|after:check_in',

            // 📝 Notes
            'remarks'            => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required'        => 'Employee is required',
            'employee_id.exists'          => 'Employee must exist',
            'business_branch_id.required' => 'Branch is required',
            'business_branch_id.exists'   => 'Branch must exist',
            'date.required'               => 'Date is required',
            'date.date_format'            => 'Date must be in mm/dd/yyyy format',
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
