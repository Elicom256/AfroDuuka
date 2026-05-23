<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateCreditSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

   public function prepareForValidation()
    {
        $businessId = Auth::user()->business_id;
        return $this->merge([
            "business_id" => $businessId
        ]);
    }
    public function rules(): array
    {
        return [
            "status" => "required|in:enabled,disabled"
        ];
    }
    
}
