<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTaxCategoryRequest extends FormRequest
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
        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            'business_branch_id' => ['required', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
