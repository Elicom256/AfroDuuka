<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

   // * Prepare data before validation.
    protected function prepareForValidation(): void
    {

        $this->merge([
            'business_branch_id' => Auth::user()->business_branch_id,
            'status' => "pending",
            "paymentStatus" => "paid"
        ]);
    }
    public function rules(): array
    {
        return [
            'business_branch_id' => 'required|exists:business_branches,id',
            'total_amount' => 'nullable|numeric',
            'status' => 'required|string|in:pending,completed,cancelled',
            'note' => 'nullable|string|min:1|max:255',
            'paymentStatus' => 'required|in:paid,pending,partial',
            'method' => 'required|in:cash,mobile_money,card,debt',
             'items' => 'required|array|min:1',
            //  sale items
             'items.*.business_branch_product_id' => 'required|exists:business_branch_products,id',
             'items.*.quantity' => 'required|integer|min:1',
             'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }
}
