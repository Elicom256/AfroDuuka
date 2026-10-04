<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $resolved = EffectiveBranchScope::branchesFor(Auth::user());
        $branchIds = $resolved === null ? null : $resolved[1];

        $taxCategoryRule = Rule::exists('tax_categories', 'id');
        if ($branchIds !== null) {
            $taxCategoryRule->whereIn('business_branch_id', $branchIds);
        }

        return [
            'tax_category_id' => [
                'sometimes',
                'required',
                'integer',
                $taxCategoryRule,
            ],
            'name' => ['sometimes', 'required', 'string', 'min:1', 'max:255'],
            'rate' => ['sometimes', 'required', 'numeric', 'between:0,1'],
            'jurisdiction_zone' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
