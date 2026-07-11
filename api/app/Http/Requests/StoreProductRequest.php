<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function prepareForValidation(): void
    {
        $user = Auth::user();
        $markup_percentage = $this->markup_percentage / 100;
        $this->merge([
            'business_branch_id' => $user?->business_branch_id,
            'status' => 'active',
            'markup_percentage' => $markup_percentage
        ]);
    }

    public function rules(): array
    {
        return [
            'business_branch_id' => ['required', 'exists:business_branches,id'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'track_serial' => ['nullable', 'boolean'],
            'quantity' => ['required', 'integer', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'markup_percentage' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['in:active,inactive,damaged,out_of_stock'],
            'expiry_date' => ['nullable', 'date'],
        ];
    }
}
