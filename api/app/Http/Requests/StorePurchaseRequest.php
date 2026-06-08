<?php

namespace App\Http\Requests;

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

        $this->merge([
            'business_id'        => $user->business_id,
            'business_branch_id' => $user->business_branch_id,
            'status'             => $this->input('status', 'completed'),
            'currency'           => $this->input('currency', 'UGX'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'supplier_id' => 'nullable|exists:suppliers,id',

            'business_id'        => 'required|exists:businesses,id',
            'business_branch_id' => 'required|exists:business_branches,id',

            // Purchase Header
            'total_amount' => 'nullable|numeric|min:0',
            'status'       => 'required|in:pending,completed,cancelled',
            'note'         => 'nullable|string|max:500',

            // Payment Information
            'payment_status_id'  => 'required|exists:payment_statuses,id',
            'reference'      => 'nullable|string|max:100',           // Invoice number, receipt, etc.
            'currency'       => 'required|string|size:3',

            // Purchase Items
            'items' => 'required|array|min:1',
            'items.*.business_branch_product_id' => 'required|exists:business_branch_products,id',
            'items.*.quantity'                   => 'required|integer|min:1',
            'items.*.cost_price'                 => 'required|numeric|min:0',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier is required for every purchase.',
            'items.required' => 'At least one product is required.',
            'items.min' => 'You must add at least one item to this purchase.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            // 👇 Add this line
             'payment_status_id.exists'   => 'Selected payment method is invalid.',
             'payment_status_id.required' => 'Payment method is required.',
        ];
    }
}