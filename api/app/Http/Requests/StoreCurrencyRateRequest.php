<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCurrencyRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => Auth::user()->business_id,
            'base_currency' => $this->input('base_currency', 'UGX'),
            'valid_from' => $this->input('valid_from', now()->toDateString()),
        ]);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'base_currency' => 'required|string|size:3',
            'target_currency' => 'required|string|size:3|different:base_currency',
            'rate' => 'required|numeric|min:0.000001',
            'source' => 'nullable|string|max:50',
            'valid_from' => 'required|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ];
    }
}
