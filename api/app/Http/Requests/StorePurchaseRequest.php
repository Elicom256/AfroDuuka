<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Prepare data before validation
     */
    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $business = $user->business()->with('country')->first();
        $defaultCurrency = $business?->country?->currency_code ?? 'UGX';

        $this->merge([
            'business_branch_id' => $this->input('business_branch_id', $user->business_branch_id),
            'status' => $this->input('status', 'pending'),
            'currency' => $this->input('currency', $defaultCurrency),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::validationBranchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            // Scoped to the caller's own business. A bare exists:suppliers,id accepted
            // any supplier in the database, and PurchaseService wrote that id straight
            // onto the purchase row. The read at PurchaseService.php:79 is tenant-scoped
            // and so returns null, which is why this went unnoticed: the purchase existed
            // and named a counterparty belonging to another tenant, and the supplier name
            // simply never rendered.
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->where(
                    'business_id',
                    Auth::user()?->business_id
                ),
            ],

            'business_branch_id' => ['required', 'integer', 'exists:business_branches,id', $branchWithinSet],

            // Purchase Header
            'total_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,completed,cancelled',
            'note' => 'nullable|string|max:500',

            // Payment Information
            'payment_status_id' => 'required|exists:payment_methods,id',
            'reference' => 'nullable|string|max:100',           // Invoice number, receipt, etc.
            'currency' => 'required|string|size:3',

            // Purchase Items
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                // Same reasoning as supplier_id. Products carry no business_id of their
                // own — they hang off business_branch_id — so the check goes through
                // the branch rather than the product.
                Rule::exists('products', 'id')->whereIn(
                    'business_branch_id',
                    fn ($query) => $query
                        ->select('id')
                        ->from('business_branches')
                        ->where('business_id', Auth::user()?->business_id)
                ),
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one product is required.',
            'items.min' => 'You must add at least one item to this purchase.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            // 👇 Add this line
            'payment_status_id.exists' => 'Selected payment method is invalid.',
            'payment_status_id.required' => 'Payment method is required.',
        ];
    }
}
