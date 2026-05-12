<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return  Auth::check();
    }

     // * Prepare data before validation.
    protected function prepareForValidation(): void
    {

        $this->merge([
            'business_id' => Auth::user()->business_id,
            'status' => "active",
        ]);
    }

     // * Get validation rules.

    public function rules(): array
    {
        return [
            'business_id' => [
                'required',
                'exists:businesses,id',
            ],

            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
            ],

            'sku' => [
                'required',
                'string',
                'max:255',

                // unique sku per business
                Rule::unique('products')->where(function ($query) {
                    return $query->where(
                        'business_id',
                        Auth::user()->business_id
                    );
                }),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:255',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'cost_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'reorder_level' => [
                'required',
                'integer',
                'min:0',
            ],

            'status' => [
                'required',
                'in:active,innactive',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}