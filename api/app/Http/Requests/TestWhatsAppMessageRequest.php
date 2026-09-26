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
        // recipient is deliberately NOT defaulted here. It used to be filled in with a
        // specific handset when the client omitted it, so a test-send request with no
        // recipient quietly sent a real message to a real person's phone. The
        // recipient is the one field a caller has to supply: it is the whole point of
        // the request, and there is no sensible stand-in for it.

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
