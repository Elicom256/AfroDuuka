<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id' => 'sometimes|exists:subscriptions,id',
            'payment_method_id' => 'sometimes|exists:payment_methods,id',
            'amount_paid' => 'sometimes|numeric|min:0',
            'transaction_id' => 'nullable|string|max:50|unique:subscription_payments,transaction_id,' . $this->route('subscription_payment'),
            'number_paid' => 'nullable|string|max:14',
            'payment_status' => 'sometimes|in:pending,completed,failed,rejected',
            'payment_proof' => 'nullable|string|max:255',
            'verified_by' => 'nullable|exists:users,id',
            'verified_at' => 'nullable|date',
            'rejection_reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
