<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $now = Carbon::now();

        $this->merge([
            'business_id'    => Auth::user()->business_id,
            'status'         => 'active',
            'starts_at'      => $now,
            'ends_at'        => $now->copy()->addMonth(),
            'trial_ends_at'  => $now->copy()->addMonth(),
        ]);
    }

    public function rules(): array
    {
        return [
            'business_id'   => ['required', 'exists:businesses,id'],
            'plan_id'       => ['required', 'exists:plans,id'],
            'status'        => ['required', 'in:active,inactive,cancelled,expired'],
            'starts_at'     => ['required', 'date'],
            'ends_at'       => ['required', 'date', 'after:starts_at'],
            'trial_ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}