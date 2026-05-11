<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateSaleItemRequest extends FormRequest
{
    // * Prepare data before validation.
    protected function prepareForValidation(): void
    {

        $this->merge([
            'business_id' => Auth::user()->business_id,
        ]);
    }
    public function rules(): array
    {
        return [
             'items' => 'required|array|min:1',
             'items.*.product_id' => 'required|exists:products,id',
             'items.*.sale_id' => 'required|exists:products,id',
             'items.*.quantity' => 'required|integer|min:1',
             'items.*.price' => 'required|numeric|min:0',
             'items.*.subtotal' => 'required|numeric|min:0',
        ];
    }
}
