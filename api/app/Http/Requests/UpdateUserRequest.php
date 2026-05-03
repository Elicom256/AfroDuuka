<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        $userId = auth()->id();
        return [
            'email' => 'nullable|email|unique:users,email,' . $userId,
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $userId,
            'phone' => 'nullable|string|max:255|unique:users,phone,' . $userId,
            'business_id' => 'nullable|exists:businesses,id',
            'role_id' => 'nullable|exists:roles,id',
        ];
    }
}
