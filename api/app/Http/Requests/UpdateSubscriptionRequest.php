<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "plan_id" => "sometimes|exists:plans,id",
            "payment_method_id" => "sometimes|exists:payment_methods,id",
            "status" => "sometimes|in:active,paused,cancelled,expired",
            "start_date" => "nullable|date",
            "end_date" => "nullable|date|after:start_date",
            "trial_ends_at" => "nullable|date",
        ];
    }
}
