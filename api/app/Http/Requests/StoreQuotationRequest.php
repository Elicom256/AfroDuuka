<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'valid_until' => 'nullable|date',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one product is required.',
            'items.min' => 'You must add at least one item to this quotation.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
