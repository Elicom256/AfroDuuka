<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBusinessBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function prepareForValidation(){
        $user = Auth::user();
        $phone = $this->input('phone');

        $this->merge([
            "business_id" => $user->business_id,
            "status" => "active",
            // Normalise a human-typed number the way StoreUserRequest does. The branch
            // form's own placeholder is "+256 700 000 000", which is 12 digits and was
            // rejected by the exact-10 rule below.
            'phone' => is_string($phone) && ($digits = preg_replace('/\D+/', '', $phone)) !== ''
                ? (str_starts_with(ltrim($phone), '+') ? '+'.$digits : $digits)
                : $phone,
        ]);
    }
    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|min:1|max:255',
            'address' => 'nullable|string|min:1|max:255',
            'phone' => 'nullable|string|min:7|max:20',
            'status' => 'required|string|in:active,innactive',
        ];
    }
}
