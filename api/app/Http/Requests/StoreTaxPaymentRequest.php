<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreTaxPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'business_branch_id' => Auth::user()?->business_branch_id,
        ]);
    }

    public function rules(): array
    {
        $branchId = Auth::user()?->business_branch_id;

        return [
            'business_branch_id' => ['required', 'integer', 'exists:business_branches,id'],
            'tax_category_id' => [
                'required',
                'integer',
                Rule::exists('tax_categories', 'id')->where('business_branch_id', $branchId),
            ],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'tax_period_start' => ['nullable', 'date', 'required_with:tax_period_end'],
            'tax_period_end' => ['nullable', 'date', 'required_with:tax_period_start', 'after_or_equal:tax_period_start'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}