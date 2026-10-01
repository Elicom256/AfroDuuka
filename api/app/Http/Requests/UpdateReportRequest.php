<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is enforced by ReportController via ReportPolicy; this only
     * validates shape.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * All fields are "sometimes" so a client can update a single column without
     * resending the whole report.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', Rule::in(['sales', 'purchases', 'inventory', 'financial', 'customers', 'suppliers'])],
            'description' => 'nullable|string',
            'parameters' => 'nullable|array',
            'schedule' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'is_active' => 'nullable|boolean',
        ];
    }
}
