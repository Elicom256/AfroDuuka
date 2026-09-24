<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        $taxBranch = $this->input('business_branch_id');
        if ($taxBranch === null) {
            $routeProduct = $this->route('product');
            $product = $routeProduct instanceof Product
                ? $routeProduct
                : Product::query()->find((int) $routeProduct);
            $taxBranch = $product?->business_branch_id;
        }

        return [
            'business_branch_id' => ['sometimes', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'tax_category_id' => [
                'nullable',
                'integer',
                Rule::exists('tax_categories', 'id')
                    ->when($taxBranch !== null, fn ($rule) => $rule->where('business_branch_id', $taxBranch)),
            ],
            'name' => ['nullable', 'string', 'min:1', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'track_serial' => ['nullable', 'boolean'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'is_tax_inclusive' => ['nullable', 'boolean'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive,damaged,out_of_stock'],
            'expiry_date' => ['nullable', 'date'],
            'change_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
