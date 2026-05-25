<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
{
    dd($validator->errors());
}
    public function rules(): array
    {
            $userId = $this->customer->user_id;

        return [
              // both
            'status' => 'nullable|in:active,inactive',
            
            // 'customer_code' => 'nullable|string|max:255|unique:customers,customer_code',
            'company_name' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',

            // user model
            // 'username' => 'required|string|max:255|unique:users,username',
            // 'phone' => 'required|string|digits:10|unique:users,phone',
            // 'nin' => 'required|string|max:255|unique:users,nin',

        'phone' => [
            'nullable',
            'string',
            'digits:10',
            Rule::unique('users', 'phone')->ignore($userId),
        ],
        'nin' => [
            'sometimes',
            'nullable',
            'string',
            'digits:10',
            Rule::unique('users', 'nin')->ignore($userId),
        ],

            'email' => 'nullable|email',
            'password' => 'nullable|string|min:6',
            'role_id' => 'nullable|exists:roles,id',
            'branch_powers' => 'nullable|in:allowed,none',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ];
    }
}
