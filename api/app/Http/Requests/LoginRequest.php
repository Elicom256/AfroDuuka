<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Credentials for the login endpoint.
 *
 * Login cannot share StoreUserRequest. That request describes an account being created
 * — firstname and phone are required columns on users — none of which a returning
 * customer sends when they log in. Coupling the two means tightening signup validation
 * silently breaks authentication for everyone who already has an account.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Users authenticate by email or by their @username; UserService::login
            // looks up either.
            'email' => 'required|string',
            'password' => 'required|string',
        ];
    }
}