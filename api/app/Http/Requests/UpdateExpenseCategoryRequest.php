<?php

namespace App\Http\Requests;

use App\Support\Auth\RolePermissions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseCategoryRequest extends FormRequest
{
    /**
     * Same defect as UpdateExpenseRequest, fixed alongside it: an unconditional
     * authorize() that only holds because routes/api.php:94 puts `role` on the
     * expenses group. canManageBranch() is what that alias resolves to.
     */
    public function authorize(): bool
    {
        return RolePermissions::canManageBranch($this->user());
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
