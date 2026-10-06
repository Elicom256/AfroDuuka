<?php

namespace App\Enums;

/**
 * What kind of event produced a cash flow row.
 *
 * These were literals spelled out in six places — the two cash-flow write requests, the
 * adjustment request, the create migration, the artisan command, and twice more in the
 * sign mapping shared by CashFlow::cashEffect() and FinanceService's SQL CASE. Any one of
 * them could drift from the others without a test noticing, which is how an adjustment
 * ended up accepted by validation with no direction to sign it.
 *
 * The single most important thing this enum records is that Adjustment has no implied
 * sign. Every other type says on its own which way money moved; an adjustment is a manual
 * correction to the ledger and carries no such information, so it must be told. That is
 * why `direction` is required for it, and why the column is nullable for the rest.
 */
enum CashFlowType: string
{
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Expense = 'expense';
    case PaymentIn = 'payment_in';
    case PaymentOut = 'payment_out';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    /**
     * Which way money moves for this type, as a multiplier on the amount.
     *
     * Null for Adjustment: there is nothing to infer from, and returning 0.0 here is what
     * keeps a directionless adjustment inert instead of silently guessed at. It is
     * excluded from the cash balance and reported separately by
     * FinanceService::dashboard() until an administrator records a direction.
     *
     * FinanceService's SQL CASE mirrors this mapping for the same figures. It cannot call
     * into PHP, so the two are held in step by FinanceCashBalanceTest, which asserts the
     * headline balance equals the running balance of the newest row.
     */
    public function sign(): ?int
    {
        return match ($this) {
            self::Sale, self::PaymentIn => 1,
            self::Purchase, self::Expense, self::PaymentOut, self::Refund => -1,
            self::Adjustment => null,
        };
    }

    /**
     * Every stored value, for validation rules that cannot reference cases directly.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Types a client may write directly through the cash-flow endpoint.
     *
     * Everything else is derived from a business event — a sale, a purchase, a return —
     * and is written by CashFlowService as a consequence of that event. Letting a caller
     * author one by hand would create a second source of revenue truth that no domain
     * event can correct, which is what rules.md means by avoiding duplicate revenue
     * calculations from multiple sources.
     *
     * Adjustment is the exception because it has no originating event: it exists to record
     * a correction an administrator is making deliberately.
     *
     * @return array<int, string>
     */
    public static function manuallyWritable(): array
    {
        return [self::Adjustment->value];
    }
}
