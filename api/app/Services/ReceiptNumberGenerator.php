<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Hands out collision-free receipt numbers.
 *
 * These used to be generated with
 * `Receipt::whereDate('created_at', today())->count() + 1`, read in one statement
 * and written in a later one. Nothing held the two together, so:
 *
 *   - two tills that rang up at the same moment read the same count, produced
 *     the same number, and the UNIQUE index rejected the loser — rolling back
 *     the whole sale, with the goods already handed over;
 *   - and it did not even need concurrency. The count ran through the tenant
 *     global scope while `receipt_number` was globally unique, so the first
 *     receipt of the day for one business and the first receipt of the day for
 *     another were both RCP-YYYYMMDD-0001. The second insert always lost.
 *
 * A native sequence fixes both in one statement. nextval() is atomic, never
 * blocks and never deadlocks, so there is no window to lose and no row for two
 * transactions to fight over.
 *
 * Sequences are also non-transactional, and that is wanted here: a number burned
 * by a sale that later rolls back is not reissued, because it may already be on a
 * printed receipt. A counter table would have to be allocated outside the sale
 * transaction to get that property, and would still serialise concurrent tills
 * behind a row lock while it did.
 *
 * The trade is that the counter is global, so a given business's numbers for a
 * day are not contiguous — the second business to trade that morning starts
 * wherever the sequence happens to be. The numbers stay unique and monotonic,
 * which is what the UNIQUE index and the audit trail actually require.
 */
class ReceiptNumberGenerator
{
    public const POS_PREFIX = 'POS-';

    public const RECEIPT_PREFIX = 'RCP-';

    /** Sequence name. Interpolated into SQL, so it is a constant and never user input. */
    private const SEQUENCE = 'receipt_number_seq';

    /**
     * Width of the serial in the rendered number. Past 10^6 the number simply
     * grows rather than wrapping.
     */
    private const WIDTH = 6;

    public function next(string $prefix, ?CarbonInterface $date = null): string
    {
        $date ??= now();

        $serial = DB::scalar('SELECT nextval(\''.self::SEQUENCE.'\')');

        if ($serial === null) {
            throw new \RuntimeException('Could not allocate a receipt number.');
        }

        return sprintf(
            '%s%s-%s',
            $prefix,
            $date->format('Ymd'),
            str_pad((string) $serial, self::WIDTH, '0', STR_PAD_LEFT)
        );
    }

    /**
     * How many numbers have ever been issued. Diagnostics and tests only —
     * never a way to generate one.
     */
    public function issuedCount(): int
    {
        return (int) DB::scalar('SELECT last_value FROM '.self::SEQUENCE);
    }
}
