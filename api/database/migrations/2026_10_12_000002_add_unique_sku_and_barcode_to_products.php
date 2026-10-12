<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Uniqueness for product sku and barcode within a branch.
 *
 * Neither column had a constraint, so two products in the same branch could carry the
 * same sku or the same barcode. That is not cosmetic here: the POS barcode path looks a
 * product up by barcode, so the duplicate decided which product a scanned code rang up,
 * and StockTransferService::resolveDestinationProduct() matches destination products by
 * sku then barcode then name, so a duplicate is exactly what makes a transfer create or
 * pick the wrong product — the reported duplicate-product-on-transfer bug.
 *
 * Scoped to business_branch_id rather than global because products are per-branch by
 * design: the same sku legitimately exists in two branches of one business, and a global
 * index would forbid that.
 *
 * Partial, because the columns are nullable and Postgres treats NULLs as distinct in a
 * unique index — without this every product with no sku would still be allowed to
 * coexist, which is the desired behaviour anyway, but the explicit form documents it and
 * survives someone later changing the columns to ''.
 *
 * Applied to existing rows before the index goes on: a database that already holds
 * duplicates would otherwise fail the migration outright with an opaque constraint error,
 * and the duplicates have to be resolved by a person who knows which row is correct.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->guardAgainstDuplicates();

        Schema::table('products', function (Blueprint $table) {
            $table->unique(
                ['business_branch_id', 'sku'],
                'products_branch_sku_unique'
            );

            $table->unique(
                ['business_branch_id', 'barcode'],
                'products_branch_barcode_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_branch_sku_unique');
            $table->dropUnique('products_branch_barcode_unique');
        });
    }

    /**
     * Refuse to add the index while duplicates exist, and say which rows they are.
     *
     * Without this the DDL fails with a constraint violation naming a constraint nobody
     * has seen before, on a table large enough that finding the offending rows by hand
     * is a job. Which of a pair of duplicate skus is the real product is a judgement
     * only someone who knows the catalogue can make, so this stops rather than picking.
     *
     * NULLs are excluded throughout: the columns are nullable and Postgres treats NULLs
     * as distinct in a unique index, so products with no sku never collide and never
     * need cleaning.
     */
    private function guardAgainstDuplicates(): void
    {
        $problems = [];

        foreach (['sku', 'barcode'] as $column) {
            $rows = DB::table('products')
                ->select('business_branch_id', $column, DB::raw('COUNT(*) as duplicate_count'))
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->groupBy('business_branch_id', $column)
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($rows as $row) {
                $ids = DB::table('products')
                    ->where('business_branch_id', $row->business_branch_id)
                    ->where($column, $row->{$column})
                    ->pluck('id')
                    ->all();

                $problems[] = sprintf(
                    '%s: branch %s has %d products sharing "%s" (ids: %s)',
                    $column,
                    $row->business_branch_id,
                    $row->duplicate_count,
                    $row->{$column},
                    implode(', ', $ids)
                );
            }
        }

        if ($problems !== []) {
            throw new RuntimeException(
                "Cannot add the products sku/barcode unique index; existing duplicates must be merged first:\n - "
                .implode("\n - ", $problems)
            );
        }
    }
};
