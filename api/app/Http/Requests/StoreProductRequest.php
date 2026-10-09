<?php

namespace App\Http\Requests;

use App\Support\Auth\RolePermissions;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * The catalogue gate is repeated here on purpose.
     *
     * FormRequest::authorize() runs before rules(), and the controller's
     * `$this->authorize('create', ...)` runs after them. Without this the denial would
     * depend on the payload: a restricted role sending a well-formed product would get
     * 403, and sending a malformed one would get 422 — as though the role were allowed
     * to try. Authoring a product is refused outright, whatever the body says.
     */
    public function authorize(): bool
    {
        return Auth::check() && RolePermissions::canCreateCatalog($this->user());
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
            $resolved = EffectiveBranchScope::validationBranchesFor(Auth::user());
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
