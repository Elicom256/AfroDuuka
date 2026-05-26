<?php

namespace App\Http\Requests;

use App\Models\Product;
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
    $user = Auth::user();

    // Generate a simple SKU: prefix + product count + random string
    $count = Product::count() + 1;
    $random = strtoupper(substr(md5(uniqid()), 0, 6)); // 6‑char random

    $sku = "SKU-" . $user->business_id . "-" . $count . "-" . $random;

    $this->merge([
        'business_id' => $user->business_id,
        'status'      => "active",
        'sku'         => $sku,
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