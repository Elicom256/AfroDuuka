<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateFinancialAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'audit_date' => ['nullable', 'date'],
            'expected_balance' => ['nullable', 'numeric', 'min:0'],
            'actual_balance' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:draft,in_progress,completed,cancelled'],
        ];
    }
}
