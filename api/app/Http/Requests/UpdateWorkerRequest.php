<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateWorkerRequest extends FormRequest
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
    public function rules(): array
    {
        return [
             /*
            |--------------------------------------------------------------------------
            | Worker-specific data
            |--------------------------------------------------------------------------
            */
            'employee_code' => 'sometimes|nullable|string|max:255|unique:workers,employee_code',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'nullable|date',
            'remarks' => 'nullable|string',

            /*
            |--------------------------------------------------------------------------
            | User model data
            |--------------------------------------------------------------------------
            */
            'firstname' => 'sometimes|nullable|string|max:255',
            'lastname' => 'sometimes|nullable|string|max:255',

            'email' => 'sometimes|nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'username' => 'nullable|string|max:255|unique:users,username',
            'phone' => 'nullable|string|digits:10|unique:users,phone',
            'nin' => 'nullable|string|max:255|unique:users,nin',
            'address' => 'nullable|string|max:255',

            /*
            |--------------------------------------------------------------------------
            | System fields (from auth context)
            |--------------------------------------------------------------------------
            */
            'business_id' => 'nullable|exists:businesses,id',
            'business_branch_id' => 'nullable|exists:business_branches,id',
            'role_id' => 'nullable|exists:roles,id',
            'branch_powers' => 'nullable|in:allowed,none',

            'status' => 'required|in:active,inactive',
        ];
    }
}
