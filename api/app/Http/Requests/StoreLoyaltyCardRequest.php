<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreLoyaltyCardRequest extends FormRequest
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
            'loyalty_program_id' => 'required|exists:loyalty_programs,id',
            'business_id' => 'required|exists:businesses,id',
            'customer_id' => 'required|exists:customers,id',
            'tier' => 'sometimes|string|in:bronze,silver,gold,platinum',
        ];
    }
}
