<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePaymentGatewayRequest extends FormRequest
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
            'provider' => ['required', 'string', Rule::in(['mtn_momo', 'airtel_money', 'flutterwave', 'pesapal'])],
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'extra_config' => 'nullable|json',
            'is_active' => 'boolean',
        ];
    }
}
