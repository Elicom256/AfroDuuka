<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateWhatsAppConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'provider' => ['sometimes', 'string', 'in:demo,meta,wati,360dialog'],
            'business_phone' => ['sometimes', 'string'],
            'phone_number_id' => ['sometimes', 'nullable', 'string'],
            'access_token' => ['sometimes', 'nullable', 'string'],
            'webhook_verify_token' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'message_template' => ['sometimes', 'nullable', 'string'],
            'welcome_message' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
