<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreWorkerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Prepare data before validation
     */
    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        // $role_id = Role::where("business_id", $user->business_id)->where("name", "worker")
        $this->merge([
            "business_id" => $user->business_id,
            "business_branch_id" => $user->business_branch_id,
            "status" => "active",
        ]);
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        
        return [
            /*
            |--------------------------------------------------------------------------
            | Worker-specific data
            |--------------------------------------------------------------------------
            */
            'employee_code' => 'nullable|string|max:255|unique:workers,employee_code',
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
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
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