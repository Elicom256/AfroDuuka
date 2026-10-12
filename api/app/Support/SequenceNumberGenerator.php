<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Hands out collision-free serial numbers for order and coupon columns.
 *
 * These were built as `Model::where('business_id', ...)->count() + 1`, which has to
 * be wrong in two separate ways:
 *
 *   - the count and the insert are separate statements, so two requests racing on
 *     the same count produce the same number and the UNIQUE index rejects the loser;
 *   - and the count ran through the tenant global scope while the columns are
 *     globally unique, so the first order of the day for one business and the first
 *     for another were both PO-000001. The second insert always lost, which reads
 *     as a constraint bug rather than a numbering bug and is very hard to diagnose
 *     from the error alone.
 *
 * A sequence fixes both in one statement. nextval() is atomic, never blocks and
 * never deadlocks, so there is no window to lose and no row for two transactions to
 * fight over. ReceiptNumberGenerator already works this way; this is the same idea for
 * the two columns that had not been converted.
 *
 * Non-transactional allocation is deliberate: a number burned by an order that later
 * rolls back is not reissued to the next one, because it may already be printed on a
 * purchase order or written on a coupon the customer holds.
 *
 * The trade is that serials are global, so one business's orders are not contiguous —
 * the second business to place an order that morning starts wherever the sequence
 * happens to be. Uniqueness and monotonicity are what the index and the audit trail
 * actually need; contiguity per tenant is cosmetic.
 */
class SequenceNumberGenerator
{
    /**
     * Sequence names. Interpolated into SQL, so they are constants and never input.
     */
    private const SEQUENCES = [
        'purchase_order' => 'purchase_order_number_seq',
        'coupon' => 'coupon_code_seq',
    ];

    /**
     * Width of the serial in the rendered value. Past this many the number simply
     * grows rather than wrapping.
     */
    private const WIDTH = 6;

    /**
     * Coupon codes render three digits after the business prefix; purchase orders
     * render six. Kept separate because the two are read by people comparing a
     * printed document against another, and a width change is a visible format change.
     */
    private const WIDTHS = [
        'purchase_order' => 6,
        'coupon' => 3,
    ];

    public function purchaseOrderNumber(): string
    {
        return 'PO-'.str_pad((string) $this->serial('purchase_order'), self::WIDTHS['purchase_order'], '0', STR_PAD_LEFT);
    }

    public function couponCode(string $businessPrefix): string
    {
        return strtoupper($businessPrefix).str_pad(
            (string) $this->serial('coupon'),
            self::WIDTHS['coupon'],
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * How many numbers have ever been issued for a kind. Diagnostics and tests only —
     * never a way to generate one.
     */
    public function issuedCount(string $kind): int
    {
        $sequence = self::SEQUENCES[$kind] ?? throw new \InvalidArgumentException("Unknown sequence kind: {$kind}");

        return (int) DB::scalar("SELECT last_value FROM {$sequence}");
    }

    private function serial(string $kind): int
    {
        $sequence = self::SEQUENCES[$kind] ?? throw new \InvalidArgumentException("Unknown sequence kind: {$kind}");

        $serial = DB::scalar("SELECT nextval('{$sequence}')");

        if ($serial === null) {
            throw new \RuntimeException("Could not allocate a {$kind} number.");
        }

        return (int) $serial;
    }
}
