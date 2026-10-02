<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBusinessBranchRequest extends FormRequest
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
            // Same normalisation StoreUserRequest applies. The branch form asks for a
            // human-typed number and its own placeholder shows a formatted one, so the
            // value arriving here is routinely "+256 700 000 000" — 12 digits, which
            // the digits_between rule below rejected outright.
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
            // 7–20 characters of the normalised value, which is wide enough for a bare
            // national number and an E.164 one. The previous exact-10 rule rejected
            // both "+256700000000" and any spaced number the owner actually typed.
            'phone' => 'nullable|string|min:7|max:20',
            'status' => 'required|string|in:active,innactive',
        ];
    }
}
