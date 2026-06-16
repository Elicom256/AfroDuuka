<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateLoyaltyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', 'string', Rule::in(['points', 'stamps', 'tiered'])],
            'points_per_currency' => 'sometimes|numeric|min:0',
            'redemption_rate' => 'sometimes|numeric|min:0',
            'expiry_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
