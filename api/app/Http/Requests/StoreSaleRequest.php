<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
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
            'business_branch_id' => $user->business_branch_id,
            'business_id'        => $user->business_id,           // Good to have for CashFlow
            'status'             => $this->input('status', 'completed'),
            'paymentStatus'      => $this->input('paymentStatus', 'paid'),
            'payment_status_id'             => $this->input('payment_status_id', null),
            'currency'           => $this->input('currency', 'UGX'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'business_branch_id' => 'required|exists:business_branches,id',
            'business_id'        => 'required|exists:businesses,id',

            // Customer (nullable - walk-in allowed)
            'customer_id' => 'nullable|exists:customers,id',

            // Sale Header
            'total_amount' => 'nullable|numeric|min:0',
            'status'       => 'required|in:pending,completed,cancelled',
            'note'         => 'nullable|string|max:500',

            // Payment Information
            'paymentStatus' => 'required|in:paid,pending,partial',
            'payment_status_id' => 'required|exists:payment_statuses,id',
            'reference'     => 'nullable|string|max:100',           // Receipt number, transaction ID, etc.
            'currency'      => 'required|string|size:3',

            // Sale Items (Required)
            'items' => 'required|array|min:1',
            'items.*.business_branch_product_id' => 'required|exists:business_branch_products,id',
            'items.*.quantity'                   => 'required|integer|min:1',
            'items.*.unit_price'                 => 'required|numeric|min:0',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one product is required.',
            'items.min' => 'At least one product must be added.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'method.in' => 'Invalid payment method selected.',
            // 👇 Add this line
             'payment_status_id.required' => 'Payment method is required.',
             'payment_status_id.exists'   => 'Selected payment method is invalid.',
        ];
    }
}