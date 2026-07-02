<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "plan_id" => "required|exists:plans,id",
            "payment_method_id" => "required|exists:payment_methods,id",
            "status" => "sometimes|in:active,paused,cancelled,expired",
            "start_date" => "nullable|date",
            "end_date" => "nullable|date|after:start_date",
            "trial_ends_at" => "nullable|date",
        ];
    }
}
