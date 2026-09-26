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
        // business_phone is deliberately NOT defaulted here. It used to be filled in
        // with a specific handset when the client omitted it, which meant a settings
        // form that simply did not ask for a number still saved one — and the business
        // then sent every message from, and could be replied to at, a stranger's phone.
        // Omitting it now fails validation, which is the correct outcome: a sending
        // identity is something the business chooses or does not have yet.

        if (! $this->has('provider')) {
            $this->merge([
                'provider' => 'demo',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // business_id is deliberately not accepted from the client. It is derived
            // from the authenticated user in the controller, so a tenant cannot create
            // a configuration against another business.
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
