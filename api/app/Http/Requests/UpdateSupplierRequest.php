<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Prepare data before validation.
     */
    protected function prepareForValidation(): void
    {
        $user = Auth::user();

        $this->merge([
            'business_id'        => $user->business_id,
            'status'             => $this->input('status', 'active'),

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
     * Validation rules.
     */
    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $userId = $supplier?->user_id;

        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [

            /*
            |--------------------------------------------------------------------------
            | User Model Data
            |--------------------------------------------------------------------------
            */

            'firstname' => 'nullable|string|max:255',
            'lastname'  => 'nullable|string|max:255',

            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId),
            ],

            'phone' => [
                'nullable',
                'string',
                'digits:10',
                Rule::unique('users', 'phone')->ignore($userId),
            ],

            'nin' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'nin')->ignore($userId),
            ],

            'password' => 'nullable|string|min:6',
            'address'  => 'nullable|string|max:255',

            /*
            |--------------------------------------------------------------------------
            | System Data
            |--------------------------------------------------------------------------
            */

            'status'             => 'required|in:active,inactive',
            'business_id'        => 'required|exists:businesses,id',
            'business_branch_id' => ['required', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'role_id'            => 'required|exists:roles,id',
            'branch_powers'      => 'nullable|in:allowed,none',

            /*
            |--------------------------------------------------------------------------
            | Supplier Data
            |--------------------------------------------------------------------------
            */

            'company_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Custom messages.
     */
    public function messages(): array
    {
        return [
            'email.unique'    => 'Email is already in use.',
            'username.unique' => 'Username is already in use.',
            'phone.unique'    => 'Phone number is already in use.',
            'nin.unique'      => 'NIN is already in use.',
        ];
    }
}