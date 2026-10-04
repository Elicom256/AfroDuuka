<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreReorderRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $this->merge([
            'business_id' => $user->business_id,
        ]);

        if ($user?->business_branch_id && ! $this->has('business_branch_id')) {
            $this->merge([
                'business_branch_id' => $user->business_branch_id,
            ]);
        }
    }

    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            'business_id' => 'required|exists:businesses,id',
            'business_branch_id' => ['required', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'product_id' => 'required|exists:products,id',
            'reorder_quantity' => 'required|integer|min:1',
            'preferred_supplier_id' => 'nullable|exists:suppliers,id',
            'auto_approve' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
