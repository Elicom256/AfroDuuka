<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateLoyaltyRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'points_required' => 'sometimes|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
            'stock' => 'sometimes|integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}
