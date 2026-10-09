<?php

namespace App\Http\Requests;

use App\Enums\CashFlowDirection;
use App\Rules\RequiresDirectionForAdjustments;
use App\Support\Auth\RolePermissions;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Edits a manual ledger row.
 *
 * `type` is immutable here. It is what every report aggregates by, and CashFlowService
 * writes it from the event that caused the row; letting a client change it would either
 * reclassify real revenue or strip a derived row of the identity its reports depend on.
 * An adjustment is the one type whose direction an administrator may set, and it has its
 * own endpoint for exactly that.
 */
class UpdateCashFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return RolePermissions::canManageSensitiveFinance($this->user());
    }

    /**
     * Run the invariant once the shape of the payload is known.
     *
     * `type` is prohibited here, so the rule reads the bound row's stored type to decide
     * whether a direction is required at all.
     */
    public function withValidator(Validator $validator): void
    {
        app(RequiresDirectionForAdjustments::class)->apply($validator);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.prohibited' => 'A cash flow keeps the type it was recorded with. Its direction may be set, but its type may not be changed.',
            'amount.min' => 'Amount must be greater than zero.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }

    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            if ($value === null || $value === '') {
                return;
            }

            $resolved = EffectiveBranchScope::validationBranchesFor(Auth::user());

            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            'type' => ['prohibited'],

            'direction' => ['nullable', Rule::enum(CashFlowDirection::class)],

            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],

            // A hand-written row has no originating event to link to, so these are off the
            // request entirely rather than merely optional. Matching the create request
            // means a row cannot be given a sale_id after the fact either.
            'customer_id' => ['prohibited'],
            'supplier_id' => ['prohibited'],
            'sale_id' => ['prohibited'],
            'purchase_id' => ['prohibited'],

            'description' => ['sometimes', 'string', 'max:500'],
            'notes' => ['sometimes', 'string', 'max:1000'],
            'category' => ['sometimes', 'string', 'max:100'],

            // Scoped to the caller's own business. Unscoped, a collision with another
            // tenant's code answers with a 422 naming their transaction, which is both a
            // cross-tenant leak and a rule that could not be satisfied anyway.
            'transaction_code' => [
                'sometimes',
                'string',
                Rule::unique('cash_flows')
                    ->where(fn ($query) => $query->where('business_id', Auth::user()->business_id))
                    ->ignore($this->route('cashFlow')),
            ],

            'business_branch_id' => ['sometimes', 'integer', 'exists:business_branches,id', $branchWithinSet],

            'payment_method' => ['sometimes', 'string', Rule::in([
                'cash', 'mobile_money', 'bank_transfer', 'card', 'cheque', 'other',
            ])],

            'reference' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['pending', 'completed', 'cancelled'])],
            'transaction_date' => ['sometimes', 'date'],
        ];
    }
}
