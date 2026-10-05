<?php

namespace App\Http\Requests;

use App\Support\Auth\RolePermissions;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashFlowAdjustmentRequest extends FormRequest
{
    /**
     * The role gates live here rather than in the controller so that a refused role is
     * answered with 403 before any validation runs.
     *
     * As controller code they sat inside `catch (\Exception)`, which turned a role
     * refusal into a 422 "Failed to create adjustment" that a caller could not tell
     * apart from a bad payload. Moving them into the request makes that unrepeatable:
     * authorization is settled by the framework before rules() is consulted.
     */
    public function authorize(): bool
    {
        return RolePermissions::canManageSensitiveFinance($this->user());
    }

    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            if ($value === null || $value === '') {
                return;
            }

            $resolved = EffectiveBranchScope::branchesFor($this->user());

            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            // transaction_code is deliberately absent: it is generated server-side.
            // It is NOT NULL and UNIQUE, and the adjustment dialog never sent one, so
            // requiring it here would only have produced a not-null violation at
            // insert time. Clients do not get to choose it either.
            'amount' => ['required', 'numeric', 'min:0'],

            // 'credit' is money into the business, 'debit' money out of it.
            'direction' => ['required', Rule::in(['credit', 'debit'])],

            'currency' => ['required', 'string', 'size:3'],
            'business_branch_id' => ['nullable', 'integer', 'exists:business_branches,id', $branchWithinSet],
            'description' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', Rule::in([
                'cash', 'mobile_money', 'bank_transfer', 'card', 'cheque', 'other',
            ])],
            'status' => ['required', Rule::in(['pending', 'completed', 'cancelled'])],
            'transaction_date' => ['required', 'date'],
            'created_by' => ['required', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'direction.required' => 'An adjustment must say whether the money came in or went out.',
            'direction.in' => 'Direction must be either credit (money in) or debit (money out).',
            'amount.min' => 'Amount must be greater than zero.',
        ];
    }
}
