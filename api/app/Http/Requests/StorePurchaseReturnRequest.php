<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_branch_id' => Auth::user()->business_branch_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'business_branch_id' => 'required|exists:business_branches,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
            'restock' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.condition' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one returned item is required.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
