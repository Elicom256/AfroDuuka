<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Support\Tenant\EffectiveBranchScope;
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
        $role_id = Role::where('business_id', $user->business_id)->where('name', 'Operations')->value('id');
        $this->merge([
            'business_id' => $user->business_id,
            'status' => 'active',
            'role_id' => $this->role_id ?? $role_id,
        ]);

        if ($user?->business_branch_id && ! $this->has('business_branch_id')) {
            $this->merge([
                'business_branch_id' => $user->business_branch_id,
            ]);
        }
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

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
            'business_branch_id' => ['nullable', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'role_id' => 'nullable|exists:roles,id',
            'branch_powers' => 'nullable|in:allowed,none',

            'status' => 'required|in:active,inactive',
        ];
    }
}
