<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Override;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    // #[Override]
    public function prepareForValidation()
    {
        return $this->merge([
            "business_id" => Auth::user()->business_id,
            "business_branch_id" => Auth::user()->business_branch_id,
            "status" => "pending"
        ]);
    }
    public function rules(): array
    {
        return [
             // Purchase fields
            'business_id' => ['required', 'exists:businesses,id'],
            'supplier_id' => ['required','exists:products,id' ],
            "business_branch_id" => ['required','exists:business_branches,id'],
            // 'total_amount' => ['nullable', 'numeric', 'min:0'],
            'status'       =>  ['required', Rule::in(['pending', 'completed', 'cancelled'])],
             'note' => ['nullable', 'string', 'min:1', 'max:255'],

             // Purchase items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id' ],
            'items.*.quantity' => [ 'required', 'integer', 'min:1' ],
            'items.*.cost_price' => [ 'required', 'numeric', 'min:0'],
            // 'items.*.subtotal' => [ 'nullable', 'numeric', 'min:0' ],
        ];
    }
    public function messages(): array
    {
        return [
            'items.required' => 'At least one purchase item is required.',
            'items.*.product_id.required' => 'Each item must have a product.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
