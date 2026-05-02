<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'nullable|string|min:6',
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users',
            'phone' => 'nullable|string|max:255|unique:users',
            'business_id' => 'nullable|exists:businesses,id',
            'role_id' => 'nullable|exists:roles,id',
        ];
    }
}
