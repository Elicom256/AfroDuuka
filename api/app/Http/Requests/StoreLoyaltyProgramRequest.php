<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreLoyaltyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => Auth::user()->business_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(['points', 'stamps', 'tiered'])],
            'points_per_currency' => 'required|numeric|min:0',
            'redemption_rate' => 'required|numeric|min:0',
            'expiry_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
