<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $this->merge([
            'business_id' => $user->business_id,
            'status' => 'active',
            'role_id' => Role::where('name', 'customer')->where('business_id', $user->business_id)->value('id'),
        ]);

        if ($user?->business_branch_id && ! $this->has('business_branch_id')) {
            $this->merge([
                'business_branch_id' => $user->business_branch_id,
            ]);
        }
    }

    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::validationBranchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            // both
            'status' => 'required|in:active,inactive',
            // customer
            // 'user_id' => 'required|exists:users,id',
            'customer_code' => 'nullable|string|max:255|unique:customers,customer_code',
            'company_name' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',

            // user model
            'email' => 'required|email',
            'password' => 'nullable|string|min:6',
            'username' => 'nullable|string|max:255|unique:users,username',
            'phone' => 'nullable|string|digits:10|unique:users,phone',
            'business_id' => 'nullable|exists:businesses,id',
            'business_branch_id' => ['nullable', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'role_id' => 'nullable|exists:roles,id',
            'branch_powers' => 'nullable|in:allowed,none',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'nin' => 'nullable|string|max:255|unique:users,nin',
            'address' => 'nullable|string|max:255',
        ];
    }
}
