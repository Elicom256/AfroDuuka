<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreWhatsAppConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('business_phone')) {
            $this->merge([
                'business_phone' => '+256731794401',
            ]);
        }

        if (! $this->has('provider')) {
            $this->merge([
                'provider' => 'demo',
            ]);
        }

        if (! $this->has('business_id')) {
            $this->merge([
                'business_id' => Auth::user()?->business_id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'business_id' => ['nullable', 'exists:businesses,id'],
            'provider' => ['nullable', 'string', 'in:demo,meta,wati,360dialog'],
            'business_phone' => ['required', 'string'],
            'phone_number_id' => ['nullable', 'string'],
            'access_token' => ['nullable', 'string'],
            'webhook_verify_token' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'message_template' => ['nullable', 'string'],
            'welcome_message' => ['nullable', 'string'],
        ];
    }
}
