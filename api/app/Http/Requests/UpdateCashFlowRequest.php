<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateCashFlowRequest extends FormRequest
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

        $this->merge([
            'business_id' => $user->business_id,
            'business_branch_id' => $user->business_branch_id,
            'updated_by' => $user->id,           // Optional: track who updated
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $cashFlowId = $this->route('cash_flow');   // assuming route model binding {cash_flow}

        return [
            'transaction_code' => [
                'sometimes',
                'string',
                Rule::unique('cash_flows')->ignore($cashFlowId)
            ],
            
            'type' => [
                'sometimes',
                'string',
                Rule::in([
                    'sale', 'purchase', 'expense', 'payment_in', 
                    'payment_out', 'refund', 'adjustment'
                ])
            ],
            
            'amount' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            
            'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
            
            'payment_method' => [
                'nullable', 
                'string', 
                Rule::in(['cash', 'mobile_money', 'bank_transfer', 'card', 'cheque', 'other'])
            ],
            
            'reference' => 'nullable|string|max:100',
            'status' => ['sometimes', Rule::in(['pending', 'completed', 'cancelled'])],
            'transaction_date' => 'sometimes|date',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Invalid transaction type.',
            'amount.min' => 'Amount must be greater than zero.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }
}