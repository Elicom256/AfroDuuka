<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            'supplier_id' => 'nullable|exists:suppliers,id',

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
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
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
