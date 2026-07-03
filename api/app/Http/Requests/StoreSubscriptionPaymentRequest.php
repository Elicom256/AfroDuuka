<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id' => 'required|exists:subscriptions,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount_paid' => 'required|numeric|min:0',
            'transaction_id' => 'nullable|string|max:50|unique:subscription_payments,transaction_id',
            'number_paid' => 'nullable|string|max:14',
            'payment_status' => 'required|in:pending,completed,failed,rejected',
            'payment_proof' => 'nullable|string|max:255',
            'verified_by' => 'nullable|exists:users,id',
            'verified_at' => 'nullable|date',
            'rejection_reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
