<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_branch_id' => $this->input('business_branch_id', Auth::user()->business_branch_id),
        ]);
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
            'business_branch_id' => ['required', 'integer', 'exists:business_branches,id', $branchWithinSet],
             'items' => 'required|array|min:1',
             'items.*.product_id' => 'required|exists:products,id',
             'items.*.sale_id' => 'required|exists:sales,id',
             'items.*.quantity' => 'required|integer|min:1',
             'items.*.price' => 'required|numeric|min:0',
             'items.*.subtotal' => 'required|numeric|min:0',
        ];
    }
}
