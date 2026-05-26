<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }
     // * Prepare data before validation
    protected function prepareForValidation(): void
    {
        $status = $this->status;
            $this->merge([
            'business_id' => Auth::user()->business_id,
            'status' => $status ?? "active"
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
                'nullable',
                'exists:categories,id',
            ],

            'name' => [
                'nullable',
                'string',
                'min:1',
                'max:255',
            ],

            'sku' => [
                'nullable',
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
                'nullable',
                'numeric',
                'min:0',
            ],

            'cost_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'quantity' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'reorder_level' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'status' => [
                'nullable',
                'in:active,innactive',
            ],

            'description' => [
                'nullable',
                'string',
                "min:1",
                "max:255"
            ],
        ];
    }
}
