<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function prepareForValidation()
    {
        $this->merge([
            'business_id' => Auth::user()->business_id,
            "business_branch_id" => Auth::user()->business_branch_id
        ]);
    }
    public function rules(): array
    {
        return [
            'email' => 'nullable|email',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'business_id' => 'nullable|exists:businesses,id',
            'business_branch_id' => 'required|exists:business_branches,id',
            'role_id' => 'nullable|exists:roles,id',
            "branch_powers" => "nullable|in:allowed,none",
            "status" => "nullable|in:active,suspended,sucked"
        ];
    }
}
