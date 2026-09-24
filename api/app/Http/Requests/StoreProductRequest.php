<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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

        if (! $this->has('status')) {
            $this->merge([
                'status' => 'active',
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
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'tax_category_id' => [
                'nullable',
                'integer',
                Rule::exists('tax_categories', 'id')
                    ->where('business_branch_id', $this->input('business_branch_id')),
            ],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'track_serial' => ['nullable', 'boolean'],
            'quantity' => ['required', 'integer', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'is_tax_inclusive' => ['nullable', 'boolean'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['in:active,inactive,damaged,out_of_stock'],
            'expiry_date' => ['nullable', 'date'],
        ];
    }
}
