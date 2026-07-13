<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['sometimes', 'required', 'exists:expense_categories,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'business_branch_id' => ['nullable', 'exists:business_branches,id'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'string', 'max:255'],
            'is_recurring' => ['nullable', 'boolean'],
            'payment_date' => ['sometimes', 'required', 'date'],
            'status' => ['nullable', 'in:pending,approved,cancelled'],
        ];
    }
}
