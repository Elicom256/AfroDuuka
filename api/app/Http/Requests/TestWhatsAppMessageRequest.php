<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TestWhatsAppMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('recipient')) {
            $this->merge([
                'recipient' => '+256731794401',
            ]);
        }

        if (! $this->has('message')) {
            $this->merge([
                'message' => 'Demo WhatsApp message from DuukaFlow.',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // E.164: leading +, then 7-15 digits. Anything else is rejected rather than
            // being passed through to the provider, where it would fail at send time.
            'recipient' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'message' => ['required', 'string', 'max:1024'],
        ];
    }
}
