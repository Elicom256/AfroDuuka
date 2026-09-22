<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function prepareForValidation(): void
    {
        $user = Auth::user();
        $this->merge([
            'business_branch_id' => $user?->business_branch_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'business_branch_id' => ['required', 'exists:business_branches,id'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'tax_category_id' => [
                'nullable',
                'integer',
                Rule::exists('tax_categories', 'id')
                    ->where('business_branch_id', $this->input('business_branch_id')),
            ],
            'name' => ['nullable', 'string', 'min:1', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'track_serial' => ['nullable', 'boolean'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'is_tax_inclusive' => ['nullable', 'boolean'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive,damaged,out_of_stock'],
            'expiry_date' => ['nullable', 'date'],
            'change_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
