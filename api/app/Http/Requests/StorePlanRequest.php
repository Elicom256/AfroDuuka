<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "pricing_id" => "required|exists:pricings,id",
            "status" => "sometimes|in:active,inactive,terminated",
        ];
    }
}
