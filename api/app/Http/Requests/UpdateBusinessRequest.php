<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBusinessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Normalise a human-typed phone number before it is validated and stored.
     *
     * Same reason as StoreUserRequest: the settings form's placeholder is a formatted
     * number, so what actually arrives is "+256 700 222 333". businesses.phone is also
     * the delivery address the notification pipeline uses for the business.
     */
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        if (is_string($phone)) {
            $digits = preg_replace('/\D+/', '', $phone);

            if ($digits !== '') {
                $this->merge([
                    'phone' => str_starts_with(ltrim($phone), '+') ? '+'.$digits : $digits,
                ]);
            }
        }
    }

/**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|min:7|max:20',
            'address' => 'nullable|string|min:1|max:255',
            'business_category_id' => 'required|exists:business_categories,id',
            // businesses.logo was in the schema from the start but was missing from
            // $fillable and from these rules, so no request could ever set it and the
            // navbar always fell back to the platform wordmark. A path or URL, never a
            // binary upload: there is no asset pipeline for it yet.
            'logo' => 'nullable|string|max:2048',
        ];
    }
}
