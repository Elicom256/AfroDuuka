<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['sales', 'purchases', 'inventory', 'financial', 'customers', 'suppliers'])],
            'description' => 'nullable|string',
            'parameters' => 'nullable|array',
            'schedule' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'is_active' => 'nullable|boolean',
        ];
    }
}
