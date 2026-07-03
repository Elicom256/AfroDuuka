<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:pricings,slug,' . $this->route('pricing'),
            'description' => 'nullable|string',
            'monthly_price' => 'sometimes|numeric|min:0',
            'yearly_price' => 'sometimes|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'limits' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'currency' => 'string|max:10',
        ];
    }
}
