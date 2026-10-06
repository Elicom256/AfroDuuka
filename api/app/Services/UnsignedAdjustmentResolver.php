<?php

namespace App\Services;

use App\Enums\CashFlowDirection;
use App\Enums\CashFlowType;
use App\Models\CashFlow;

/**
 * Decides what "this adjustment has no direction" means.
 *
 * The rule the whole adjustment path turns on: an adjustment must say whether the money
 * came in or went out. It is enforced in three places — the create request, the update
 * request, and the cash_flows_adjustment_requires_direction CHECK — and repairing a
 * violation is the fourth. Those answers have to agree, so they are asked here once and
 * both the HTTP endpoint and the artisan command read from the same place.
 *
 * This holds no sign logic. CashFlow::cashEffect() remains the single reader of what a
 * direction means, so there is nothing here to fall out of step with the balance.
 */
class UnsignedAdjustmentResolver
{
    /**
     * Why a direction cannot be recorded on this row right now.
     *
     * Null means it can. A string is the message to return to the caller, phrased for a
     * person rather than a log, because both callers surface it directly.
     */
    public function refusalFor(CashFlow $cashFlow): ?string
    {
        if ($cashFlow->type !== CashFlowType::Adjustment->value) {
            return "This transaction is a {$cashFlow->type}, not an adjustment. Its direction comes from its type and cannot be set by hand.";
        }

        if ($cashFlow->direction !== null) {
            return "This adjustment already has the direction [{$cashFlow->direction}]. Nothing changed.";
        }

        return null;
    }

    /**
     * Record a direction on an adjustment that lacks one.
     *
     * Throws rather than returning false, because every caller here is a command line or a
     * validated HTTP request: there is no useful partial outcome, and a silent no-op on a
     * ledger write is exactly the kind of thing that gets read as a success.
     *
     * @throws \RuntimeException
     */
    public function apply(CashFlow $cashFlow, CashFlowDirection $direction): CashFlow
    {
        if ($message = $this->refusalFor($cashFlow)) {
            throw new \RuntimeException($message);
        }

        // forceFill rather than fill, because `direction` is in $fillable already but this
        // also runs for rows fetched without global scopes, where nothing about the caller
        // has been established. It would be a mistake for a silent assignment filter to
        // turn a repair into a no-op.
        $cashFlow->forceFill(['direction' => $direction->value])->save();

        return $cashFlow;
    }
}
