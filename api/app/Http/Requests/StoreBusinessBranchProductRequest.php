<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreBusinessBranchProductRequest extends FormRequest
{
    /**
     * Allow request (fix critical bug)
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Inject branch + default status
     */
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

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'business_branch_id' => ['required', 'exists:business_branches,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'markup_percentage' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'name' => ['nullable', 'string', 'min:1', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['in:active,inactive,damaged,out_of_stock'],
        ];
    }
}