<?php

namespace App\Enums;

/**
 * Which way money moved through the ledger.
 *
 * Credit is money into the business, debit is money out of it. The word credit is
 * deliberately not the same as a sales credit here: it describes the direction of a cash
 * movement, not a document.
 *
 * `direction` is nullable on cash_flows because six of the seven types already imply their
 * sign — CashFlow::cashEffect() reads it first and only falls back to the type. Adjustment
 * is the one type with no implied sign, so a directionless adjustment is a hole in the
 * books rather than an ordinary row.
 */
enum CashFlowDirection: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Sign multiplier, mirroring CashFlow::cashEffect().
     */
    public function sign(): int
    {
        return match ($this) {
            self::Credit => 1,
            self::Debit => -1,
        };
    }
}
