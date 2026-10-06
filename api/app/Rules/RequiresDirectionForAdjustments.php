<?php

namespace App\Rules;

use App\Enums\CashFlowDirection;
use App\Enums\CashFlowType;
use App\Models\CashFlow;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Validator;

/**
 * The rule that gives every cash flow row a known cash sign.
 *
 * An adjustment has no type to infer a direction from, so without one it is excluded from
 * the cash balance entirely — CashFlow::cashEffect() returns 0.0 and the reported figure is
 * quietly short. The database refuses such a row via the
 * cash_flows_adjustment_requires_direction CHECK, but a CHECK answers with a 500 by way of
 * a QueryException, which is no use to a client that sent a bad field.
 *
 * This is the same rule at the application layer, so the failure is a 422 naming the field.
 * Both the create and the update request call it, so neither can end up accepting what the
 * other rejects.
 *
 * Deliberately NOT a rule object attached to the `direction` key. Laravel skips every rule
 * on an attribute that is absent from the payload, and an absent `direction` is exactly the
 * case this has to catch: attached directly, the request passed validation and the database
 * CHECK threw — the 500 this exists to prevent. So the requests call apply() from
 * withValidator(), where it runs whether or not the field was sent.
 */
class RequiresDirectionForAdjustments
{
    /**
     * Run the check against a payload, reporting failures against `direction`.
     */
    public function validatePayload(string $type, mixed $direction, Validator $validator): void
    {
        $direction = is_string($direction) && trim($direction) !== '' ? trim($direction) : null;

        if ($type === CashFlowType::Adjustment->value) {
            if ($direction === null) {
                $validator->errors()->add('direction', 'An adjustment must say whether the money came in or went out.');

                return;
            }

            if (CashFlowDirection::tryFrom($direction) === null) {
                $validator->errors()->add('direction', 'Direction must be either credit (money in) or debit (money out).');
            }

            return;
        }

        // A direction on any other type would be silently ignored by cashEffect(), which
        // only reads it for an adjustment. Letting one through would store a value that
        // looks authoritative and affects nothing.
        if ($direction !== null) {
            $validator->errors()->add('direction', 'Only an adjustment records a direction; this type takes its sign from the type itself.');
        }
    }

    /**
     * Register the check on a validator, whichever way the request was wired.
     */
    public function apply(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $validator->getData();

            // On update `type` is prohibited, so the stored row's own type is what decides.
            $type = $data['type'] ?? $this->storedType();

            $this->validatePayload(is_string($type) ? $type : '', $data['direction'] ?? null, $validator);
        });
    }

    /**
     * The bound row's type, when the request is an update.
     */
    private function storedType(): ?string
    {
        $cashFlow = Route::current()?->parameter('cashFlow');

        return $cashFlow instanceof CashFlow ? $cashFlow->type : null;
    }
}
