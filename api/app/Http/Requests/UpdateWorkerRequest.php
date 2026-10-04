<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $role_id = Role::where('business_id', $user->business_id)->where('name', 'Operations')->value('id');
        $this->merge([
            'business_id' => $user->business_id,
            'status' => $this->input('status', 'active'),
            'role_id' => $this->input('role_id', $role_id),
        ]);

        if ($user?->business_branch_id && ! $this->has('business_branch_id')) {
            $this->merge([
                'business_branch_id' => $user->business_branch_id,
            ]);
        }
    }

    public function rules(): array
    {
        // Adjust route param names to match your routes
        $worker = $this->route('worker');
        $userId = $worker->user_id;

        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [

            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'intern'])],
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'nullable|date',
            'remarks' => 'nullable|string',

            'firstname' => 'sometimes|nullable|string|max:255',
            'lastname' => 'sometimes|nullable|string|max:255',
            'email' => [
                'required', 'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => 'nullable|string|min:6',
            'username' => [
                'nullable', 'string', 'max:255',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'phone' => [
                'nullable', 'string', 'digits:10',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'nin' => [
                'nullable', 'string',
                Rule::unique('users', 'nin')->ignore($userId),
            ],
            'address' => 'nullable|string|max:255',
            'business_branch_id' => ['nullable', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'status' => 'required|in:active,inactive',
        ];
    }
}
