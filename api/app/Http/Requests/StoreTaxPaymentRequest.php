<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
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
        $user = Auth::user();

        if ($user?->business_branch_id && ! $this->has('business_branch_id')) {
            $this->merge([
                'business_branch_id' => $user->business_branch_id,
            ]);
        }
    }

    public function rules(): array
    {
        $resolved = EffectiveBranchScope::branchesFor(Auth::user());
        $branchIds = $resolved === null ? null : $resolved[1];

        $branchWithinSet = function ($attribute, $value, $fail) use ($branchIds) {
            if ($branchIds !== null && ! in_array((int) $value, $branchIds, true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        $taxCategoryRule = Rule::exists('tax_categories', 'id');
        if ($branchIds !== null) {
            $taxCategoryRule->whereIn('business_branch_id', $branchIds);
        }

        return [
            'business_branch_id' => ['required', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'tax_category_id' => [
                'required',
                'integer',
                $taxCategoryRule,
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
