<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateCurrencyRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'target_currency' => 'sometimes|string|size:3',
            'rate' => 'sometimes|numeric|min:0.000001',
            'source' => 'nullable|string|max:50',
            'valid_from' => 'sometimes|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ];
    }
}
