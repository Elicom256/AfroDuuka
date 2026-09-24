<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreCashFlowRequest extends FormRequest
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
            'created_by' => $user->id,
            'status' => $this->input('status', 'completed'),
            'currency' => $this->input('currency', $defaultCurrency),
            'transaction_date' => $this->input('transaction_date', now()->toDateString()),
            
            // Auto-generate transaction code if not provided
            'transaction_code' => $this->input('transaction_code') ?? 
                'CF-' . str_pad(rand(1000, 999999), 6, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            if ($value === null || $value === '') {
                return;
            }
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            'transaction_code' => 'required|string|unique:cash_flows,transaction_code',
            'type' => ['required', 'string', Rule::in([
                'sale', 'purchase', 'expense', 'payment_in', 
                'payment_out', 'refund', 'adjustment'
            ])],
            
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            
            'business_branch_id' => ['nullable', 'integer', 'exists:business_branches,id', $branchWithinSet],
            
            // Relationships
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            
            'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
            
            'payment_method' => ['nullable', 'string', Rule::in([
                'cash', 'mobile_money', 'bank_transfer', 'card', 'cheque', 'other'
            ])],
            
            'reference' => 'nullable|string|max:100',
            'status' => ['required', Rule::in(['pending', 'completed', 'cancelled'])],
            'transaction_date' => 'required|date',
            'created_by' => 'required|exists:users,id',
        ];
    }

    /**
     * Custom error messages (optional but recommended)
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Invalid transaction type. Allowed: sale, purchase, expense, etc.',
            'amount.min' => 'Amount must be greater than zero.',
            'payment_method.in' => 'Invalid payment method.',
        ];
    }
}