<?php

namespace App\Http\Requests;

use App\Enums\CashFlowDirection;
use App\Support\Auth\RolePermissions;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Records the direction on an adjustment written before one was required.
 *
 * This exists because the dashboard tells a user that an adjustment with no direction is
 * excluded from the cash balance and that somebody must fix it. Until now the only way to
 * fix one was a shell and an artisan command, so the instruction was not actionable by the
 * people who saw it.
 *
 * The role gate is the same one that guards creating an adjustment, because this writes the
 * same column: deciding which way money moved is a finance decision, not a branch one.
 * Branch containment follows, so a BranchManager can only repair rows in their own scope.
 * Cross-tenant repair stays with the artisan command, which is the only writer that runs
 * without global scopes.
 */
class UpdateCashFlowDirectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return RolePermissions::canManageSensitiveFinance($this->user());
    }

    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            $cashFlow = $this->route('cashFlow');

            if ($cashFlow === null) {
                return;
            }

            $resolved = EffectiveBranchScope::branchesFor($this->user());

            if ($resolved !== null && ! in_array((int) $cashFlow->business_branch_id, $resolved[1], true)) {
                $fail('This transaction is outside your branch scope.');
            }
        };

        return [
            'direction' => ['required', Rule::enum(CashFlowDirection::class), $branchWithinSet],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'direction.required' => 'Say whether the money came in or went out.',
            'direction.enum' => 'Direction must be either credit (money in) or debit (money out).',
        ];
    }
}
