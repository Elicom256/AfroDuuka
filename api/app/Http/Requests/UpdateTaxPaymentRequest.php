<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateTaxPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $branchId = Auth::user()?->business_branch_id;

        return [
            'tax_category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('tax_categories', 'id')->where('business_branch_id', $branchId),
            ],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'payment_date' => ['sometimes', 'required', 'date'],
            'tax_period_start' => ['nullable', 'date', 'required_with:tax_period_end'],
            'tax_period_end' => ['nullable', 'date', 'required_with:tax_period_start', 'after_or_equal:tax_period_start'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
