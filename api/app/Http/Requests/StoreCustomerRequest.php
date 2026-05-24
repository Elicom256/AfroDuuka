<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

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
            "business_id" => $user->business_id,
            "business_branch_id" => $user->business_branch_id,
            'status' => 'active',
            "role_id" => Role::where("name", "customer")->where("business_id", $user->business_id)->value("id")
        ]);
    }

    public function rules(): array
    {
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
            'business_branch_id' => 'nullable|exists:business_branches,id',
            'role_id' => 'nullable|exists:roles,id',
            'branch_powers' => 'nullable|in:allowed,none',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'nin' => 'nullable|string|max:255|unique:users,nin',
            'address' => 'nullable|string|max:255',
        ];
    }
}