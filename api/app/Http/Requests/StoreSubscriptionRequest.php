<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $now = Carbon::now();
        $user = Auth::user();

        if ($user->business_id) {
            $businessId = $user->business_id;
        } elseif (! $this->filled('business_id')) {
            abort(422, 'business_id is required for CoreSupport subscriptions.');
        } else {
            $businessId = $this->input('business_id');
        }

        $this->merge([
            'business_id' => $businessId,
            'status' => 'active',
            'starts_at' => $now,
            'ends_at' => $now->copy()->addMonth(),
            'trial_ends_at' => $now->copy()->addMonth(),
        ]);
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'exists:businesses,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', 'in:active,inactive,cancelled,expired'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'trial_ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
