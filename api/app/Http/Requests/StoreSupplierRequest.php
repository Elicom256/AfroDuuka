<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $user = Auth::user();

        $this->merge([
            'business_id' => $user->business_id,
            'status' => 'active',

            'role_id' => Role::where('name', 'supplier')
                ->where('business_id', $user->business_id)
                ->value('id'),
        ]);

        if ($user?->business_branch_id && ! $this->has('business_branch_id')) {
            $this->merge([
                'business_branch_id' => $user->business_branch_id,
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
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
            | Shared/User Model Data
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
            'status' => 'required|in:active,inactive',
            'business_id' => 'nullable|exists:businesses,id',
            'business_branch_id' => ['nullable', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'role_id' => 'nullable|exists:roles,id',
            'branch_powers' => 'nullable|in:allowed,none',

            /*
            |--------------------------------------------------------------------------
            | Supplier Model Data
            |--------------------------------------------------------------------------
            */

            'company_name' => 'nullable|string|max:255',
        ];
    }
}
