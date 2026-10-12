<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sequences behind purchase_order.order_number and coupon.code.
 *
 * Both columns are UNIQUE, and both were handed out from
 * `Model::where('business_id', ...)->count() + 1` — read in one statement, written
 * in a later one, with nothing holding the two together.
 *
 * That was wrong twice over. Two requests creating an order at the same moment read
 * the same count and produced the same number, and the UNIQUE index rejected the
 * loser. And because the count was tenant-scoped while the column is not, two
 * businesses that happened to share a name prefix both started at 001 — the second
 * insert always lost, and neither could be diagnosed as a race from the error alone.
 *
 * A sequence fixes both in one statement: nextval() is atomic, never blocks and
 * never rolls back, so a number consumed by an order that later fails is not
 * reissued. See App\Support\SequenceNumberGenerator.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Seeded from the highest serial already in use so a database that already
        // holds rows does not restart numbering at 1 and collide with them.
        foreach ([
            'purchase_order_number_seq' => 'purchase_orders.order_number',
            'coupon_code_seq' => 'coupons.code',
        ] as $sequence => $column) {
            DB::statement("CREATE SEQUENCE IF NOT EXISTS {$sequence}");

            [$table, $field] = explode('.', $column);

            $highest = DB::table($table)
                ->whereNotNull($field)
                ->get()
                ->map(fn ($row) => (int) preg_replace('/\D/', '', (string) $row->{$field}))
                ->max() ?? 0;

            if ($highest > 0) {
                DB::statement("SELECT setval('{$sequence}', {$highest}, true)");
            }
        }
    }

    public function down(): void
    {
        DB::statement('DROP SEQUENCE IF EXISTS purchase_order_number_seq');
        DB::statement('DROP SEQUENCE IF EXISTS coupon_code_seq');
    }
};
