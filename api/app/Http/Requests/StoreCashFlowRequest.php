<?php

namespace App\Http\Requests;

use App\Enums\CashFlowDirection;
use App\Enums\CashFlowType;
use App\Rules\RequiresDirectionForAdjustments;
use App\Support\Auth\RolePermissions;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Writes a manual ledger row.
 *
 * Two decisions here rather than in the controller. Authorization is settled by
 * `authorize()` before any rule runs, so a refused role gets a 403 instead of a 422 it
 * cannot tell apart from a bad payload. And the type is confined to `adjustment`, because
 * every other type is a consequence of a business event — a sale, a purchase, a return —
 * that CashFlowService already writes. Accepting `type: 'sale'` from a client would let
 * anyone who got this far invent revenue that the dashboard then reports as real.
 */
class StoreCashFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return RolePermissions::canManageSensitiveFinance($this->user());
    }

    /**
     * Prepare data before validation.
     *
     * Everything the client is not allowed to decide is settled here, so a crafted
     * payload cannot argue with it: the transaction code, the branch (constrained to the
     * caller's scope below), and who is recorded as having written the row.
     */
    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $business = $user->business()->with('country')->first();

        $this->merge([
            'business_branch_id' => $this->input('business_branch_id') ?? $user->business_branch_id,
            'created_by' => $user->id,
            'status' => $this->input('status', 'completed'),
            'currency' => $this->input('currency') ?? ($business?->country?->currency_code ?? 'UGX'),
            'transaction_date' => $this->input('transaction_date') ?? now()->toDateString(),

            // Always generated, never accepted. The code is UNIQUE and NOT NULL, and it is
            // what identifies the row on every report, receipt and exported statement, so a
            // client picking it would let one tenant collide with or squat on another's
            // code. The ULID form matches FinanceController::adjustment(): sortable by
            // creation time and free of the rand() collisions the previous padding had.
            'transaction_code' => 'CF-ADJ-'.Str::ulid(),
        ]);
    }

    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            if ($value === null || $value === '') {
                return;
            }

            $resolved = EffectiveBranchScope::branchesFor(Auth::user());

            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            'transaction_code' => 'required|string|unique:cash_flows,transaction_code',

            'type' => [
                'required',
                'string',
                Rule::in(CashFlowType::manuallyWritable()),
            ],

            // Shape only. Whether a direction is required at all depends on `type`, and an
            // absent `direction` is the case that matters, so the rule itself runs in
            // withValidator() below where it cannot be skipped.
            'direction' => ['nullable', Rule::enum(CashFlowDirection::class)],

            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => 'required|string|size:3',

            'business_branch_id' => ['nullable', 'integer', 'exists:business_branches,id', $branchWithinSet],

            // Deliberately no customer_id, supplier_id, sale_id or purchase_id. Those
            // columns exist to tie a row to the event that caused it, and a hand-written
            // adjustment has no such event; the refund path is what links a return to the
            // sale it reverses.

            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',

            'payment_method' => ['nullable', 'string', Rule::in([
                'cash', 'mobile_money', 'bank_transfer', 'card', 'cheque', 'other',
            ])],

            'reference' => 'nullable|string|max:100',
            'status' => ['required', Rule::in(['pending', 'completed', 'cancelled'])],
            'transaction_date' => 'required|date',
        ];
    }

    /**
     * Run the invariant once the shape of the payload is known.
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
            'type.in' => 'Only a manual adjustment can be written directly. Sales, purchases, expenses and returns are recorded when the underlying event happens.',
            'amount.min' => 'Amount must be greater than zero.',
            'payment_method.in' => 'Invalid payment method.',
        ];
    }
}
