<?php

namespace App\Http\Requests;

use App\Support\Auth\RolePermissions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    /**
     * Was `return true`, on the grounds that the route group at routes/api.php:94
     * carries the `role` middleware and the request had nothing to add. That is true
     * today and is the whole risk: a FormRequest that authorizes unconditionally will
     * silently authorize anything the day that group is dropped, and nothing about the
     * request object says so.
     *
     * canManageBranch() is what the `role` alias resolves to in RequireRole, so this
     * restates the existing rule rather than inventing a second one. Expenses decide
     * whether spend is allowed — the distinction BusinessDebitController@pay relies on
     * when it stays open to the floor.
     */
    public function authorize(): bool
    {
        return RolePermissions::canManageBranch($this->user());
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['sometimes', 'required', 'exists:expense_categories,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'business_branch_id' => ['nullable', 'exists:business_branches,id'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'string', 'max:255'],
            'is_recurring' => ['nullable', 'boolean'],
            'payment_date' => ['sometimes', 'required', 'date'],
            'status' => ['nullable', 'in:pending,approved,cancelled'],
        ];
    }
}
