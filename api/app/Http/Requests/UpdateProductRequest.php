<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }
     * Prepare data before validation
    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => Auth::user()->business_id,
            'status' => true,
        ]);
    }

     // * Get the validation rules that apply to the request.
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
                'nullable',
                'integer',
                'min:0',
            ],

            'status' => [
                'required',
                'boolean',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
