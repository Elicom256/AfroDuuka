<?php

namespace App\Rules;

use App\Enums\CashFlowDirection;
use App\Enums\CashFlowType;
use App\Models\CashFlow;
use Illuminate\Support\Facades\Route as RouteFacade;
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
    public const MESSAGE_MISSING = 'An adjustment must say whether the money came in or went out.';

    public const MESSAGE_UNKNOWN = 'Direction must be either credit (money in) or debit (money out).';

    public const MESSAGE_NOT_APPLICABLE = 'Only an adjustment records a direction; this type takes its sign from the type itself.';

    public const MESSAGE_CLEARED = 'This adjustment already records a direction. To change which way it moved, repair it instead of clearing it.';

    /**
     * Run the check against a payload, reporting failures against `direction`.
     *
     * $cashFlow is the stored row, or null when this is a create. It is what separates
     * the two situations that superficially look alike when `direction` is missing.
     *
     * On a create there is no stored row, so an adjustment with no direction is a row that
     * would be born unsigned — that is refused.
     *
     * On an update, a missing direction means "leave it alone", which is never a new hole.
     * Even on a row that is already unsigned: those rows predate the requirement and are
     * signed through PATCH /finances/adjustments/{id}/direction, and refusing every other
     * edit until they are repaired would freeze a row nobody can describe or correct. A
     * hole with a stale label on it is more workable than a hole the API will not let you
     * mention. The one payload that does open a hole is an explicit null on a row that is
     * currently signed, and that is refused.
     */
    public function validatePayload(string $type, mixed $direction, Validator $validator, ?CashFlow $cashFlow = null): void
    {
        $direction = is_string($direction) && trim($direction) !== '' ? trim($direction) : null;

        if ($type !== CashFlowType::Adjustment->value) {
            // A direction on any other type would be silently ignored by cashEffect(),
            // which only reads it for an adjustment. Letting one through would store a
            // value that looks authoritative and affects nothing.
            if ($direction !== null) {
                $validator->errors()->add('direction', self::MESSAGE_NOT_APPLICABLE);
            }

            return;
        }

        if ($direction !== null) {
            if (CashFlowDirection::tryFrom($direction) === null) {
                $validator->errors()->add('direction', self::MESSAGE_UNKNOWN);
            }

            return;
        }

        if ($cashFlow === null) {
            $validator->errors()->add('direction', self::MESSAGE_MISSING);

            return;
        }

        if ($this->isClearingSignedRow() && $cashFlow->direction !== null) {
            $validator->errors()->add('direction', self::MESSAGE_CLEARED);
        }
    }

    /**
     * Register the check on a validator, whichever way the request was wired.
     */
    public function apply(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $validator->getData();
            $cashFlow = $this->storedCashFlow();

            // On update `type` is prohibited, so the stored row's own type is what decides.
            $type = $data['type'] ?? $cashFlow?->type;

            $this->validatePayload(
                is_string($type) ? $type : '',
                $data['direction'] ?? null,
                $validator,
                $cashFlow,
            );
        });
    }

    /**
     * The bound row, when the request is an update.
     */
    private function storedCashFlow(): ?CashFlow
    {
        $cashFlow = RouteFacade::current()?->parameter('cashFlow');

        return $cashFlow instanceof CashFlow ? $cashFlow : null;
    }

    /**
     * Did this payload send `direction` as an explicit null, rather than omit it?
     *
     * The distinction decides whether a signed row is being cleared or merely left alone.
     * Laravel has already coerced an absent field to null in getData(), so the request's
     * raw input is the only place the difference survives.
     */
    private function isClearingSignedRow(): bool
    {
        return request()->has('direction') && request()->input('direction') === null;
    }
}
