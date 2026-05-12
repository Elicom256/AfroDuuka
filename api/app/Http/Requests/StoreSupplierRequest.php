<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreSupplierRequest extends FormRequest
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
        return $this->merge([
            "business_id" => Auth::user()->business_id,
        ]);
    }
    public function rules(): array
    {
        return [
            'business_id' => "required|exists:businesses,id",
            'name' => "nullable|string|min:1|max:255", 
            'email' => "required|email",
            'phone' => "required|string|min:1|max:255",
            'address' => "nullable|string|min:1|max:255",
            'status' => "nullable|string|min:1|max:255", 
            ];
    }
}
