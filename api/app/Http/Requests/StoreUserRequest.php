<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise the phone before it is validated.
     *
     * users.phone is NOT NULL and UNIQUE, so a missing value is a 500 rather than a
     * validation error, and a formatted value like "+256 700 000 000" would store
     * with its separators and then fail uniqueness against the same number typed
     * differently. Signup collects a human-typed number, so strip formatting here
     * and keep one canonical representation in the column.
     */
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        if (is_string($phone)) {
            $digits = preg_replace('/\D+/', '', $phone);

            if ($digits !== '') {
                // Keep the leading + so the stored value is E.164-shaped, but fall back
                // to raw digits when the caller already supplied the country code.
                $normalised = str_starts_with(ltrim($phone), '+')
                    ? '+'.$digits
                    : $digits;

                $this->merge(['phone' => $normalised]);
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
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'firstname' => 'required|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            // No `unique` rule here on purpose. The username is derived from the first
            // name by the signup form, so a second Jane is refused with a complaint
            // about a field she never typed. UserService::uniqueUsername() resolves the
            // collision instead (@jane, @jane2, ...) and the column's unique index is
            // the backstop. Validating it here as well just rejected a legitimate
            // signup once the service stopped double-prefixing the value.
            'username' => 'nullable|string|max:40',
            'phone' => 'required|string|max:20|unique:users',
            'business_id' => 'nullable|exists:businesses,id',
            'business_branch_id' => 'nullable|exists:business_branches,id',
            'role_id' => [
                'nullable',
                Rule::exists('roles')->where(function ($query) {
                    $query->where('business_id', request('business_id'));
                }),
            ],
            'branch_powers' => 'nullable|in:allowed,none',
            'status' => 'nullable|in:active,suspended,sucked',
        ];
    }
}
