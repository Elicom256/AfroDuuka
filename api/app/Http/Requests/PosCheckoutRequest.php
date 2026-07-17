<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PosCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $business = $user->business()->with('country')->first();
        $defaultCurrency = $business?->country?->currency_code ?? 'UGX';

        $this->merge([
            'business_branch_id' => $user->business_branch_id,
            'business_id'        => $user->business_id,
            'currency'           => $this->input('currency', $defaultCurrency),
        ]);
    }

    public function rules(): array
    {
        return [
            'business_branch_id' => 'required|exists:business_branches,id',
            'business_id'        => 'required|exists:businesses,id',

            'customer_id' => 'nullable|exists:customers,id',

            'sale_id' => 'nullable|exists:sales,id',

            'items' => 'required_without:sale_id|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.discount'     => 'nullable|numeric|min:0',

            'payments' => 'required|array|min:1',
            'payments.*.method' => ['required', 'string', Rule::in(['cash', 'mobile_money', 'card', 'credit'])],
            'payments.*.amount' => 'required|numeric|min:0',

            'note'     => 'nullable|string|max:500',
            'currency' => 'required|string|size:3',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one product to the cart.',
            'items.min' => 'Add at least one product to the cart.',
            'payments.required' => 'At least one payment method is required.',
            'payments.min' => 'At least one payment method is required.',
        ];
    }
}
