<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\Auth\RolePermissions;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Everything that describes what a product *is*. A restricted role may not touch
     * any of it — the catalogue belongs to the roles that built it.
     */
    private const CATALOG_FIELDS = [
        'business_branch_id',
        'product_category_id',
        'tax_category_id',
        'name',
        'sku',
        'barcode',
        'track_serial',
        'cost_price',
        'selling_price',
        'is_tax_inclusive',
        'reorder_level',
        'description',
        'status',
        'expiry_date',
        'change_reason',
    ];

    /**
     * Mirrors the reason codes InventoryService::adjust() accepts, so a bad reason is
     * a 422 here rather than an InvalidArgumentException mid-transaction.
     */
    private const ADJUSTMENT_REASONS = [
        'adjustment',
        'damaged',
        'expired',
        'lost',
        'stock_take',
        'other',
    ];

    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        if (RolePermissions::isRestricted($this->user())) {
            return $this->quantityOnlyRules();
        }

        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::validationBranchesFor(Auth::user());
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

    /**
     * A restricted role may only move stock.
     *
     * `quantity` is the resulting level, not a delta, because that is what a stock
     * count produces. The controller diffs it against the current level and books the
     * difference through InventoryService::adjust() so the movement is recorded.
     *
     * Catalogue fields are marked `prohibited` rather than merely omitted: dropping
     * them silently would answer 201 to a request that changed nothing, and the caller
     * would believe a rename had landed.
     */
    private function quantityOnlyRules(): array
    {
        $rules = [
            'quantity' => ['required', 'integer', 'min:0'],
            'adjustment_reason' => ['nullable', 'string', Rule::in(self::ADJUSTMENT_REASONS)],
            'adjustment_notes' => ['nullable', 'string', 'max:1000'],
        ];

        foreach (self::CATALOG_FIELDS as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
    }
}
