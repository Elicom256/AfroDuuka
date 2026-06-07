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

        $attendances = $this->input('attendances', []);

        foreach ($attendances as $key => $attendance) {
            $status = $attendance['status'] ?? null;

            $attendances[$key]['business_branch_id'] = $branchId;

            // Auto set check_in based on status
            if (in_array($status, ['present', 'late'])) {
                $attendances[$key]['check_in'] = Carbon::now()->toDateTimeString();
                $attendances[$key]['remarks'] = "reported for work today! Recorded as present";
            } else {
                $attendances[$key]['check_in'] = null;
                $attendances[$key]['remarks'] = "did not report for work today! Recorded as absent";
            }
        }

        $this->merge([
            'attendances' => $attendances,
        ]);
    }

    public function rules(): array
    {
        return [
            'attendances' => 'required|array|min:1',
            'attendances.*.worker_id' => 'required|exists:workers,id',
            'attendances.*.session' => 'required|in:morning,afternoon,evening,night',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.check_in' => 'nullable|date',        // Still keep for validation
            'attendances.*.check_out' => 'nullable|date|after_or_equal:attendances.*.check_in',
            'attendances.*.remarks' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'attendances.*.worker_id.required' => 'Employee is required',
            'attendances.*.worker_id.exists' => 'Employee must exist',
            'attendances.*.session.required' => 'Session is required',
            'attendances.*.status.required' => 'Status is required',
            'attendances.*.check_out.after_or_equal' => 'Check-out must be after or equal to check-in',
            'attendances.*.remarks.max' => 'Remarks may not exceed 255 characters',
        ];
    }
}