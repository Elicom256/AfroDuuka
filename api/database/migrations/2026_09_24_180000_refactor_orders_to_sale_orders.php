<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reconciles databases that were built before the 2026_07_18 migration was
     * edited to create `sale_orders` / `sale_order_items` directly.
     *
     * Fresh databases no-op every branch: the create migrations already name the
     * tables correctly and ship the status check constraint.
     */
    public function up(): void
    {
        $legacy = Schema::hasTable('orders') && ! Schema::hasTable('sale_orders');

        if ($legacy) {
            Schema::rename('orders', 'sale_orders');
            Schema::rename('order_items', 'sale_order_items');

            Schema::table('sale_order_items', function (Blueprint $table) {
                $table->renameColumn('order_id', 'sale_order_id');
            });
        }

        $this->ensureStatusCheck('sale_orders');
        $this->ensureStatusCheck('purchase_orders');
    }

    public function down(): void
    {
        $this->dropStatusCheck('sale_orders');
        $this->dropStatusCheck('purchase_orders');

        if (Schema::hasTable('sale_order_items')) {
            if (Schema::hasColumn('sale_order_items', 'sale_order_id')) {
                Schema::table('sale_order_items', function (Blueprint $table) {
                    $table->renameColumn('sale_order_id', 'order_id');
                });
            }
            Schema::rename('sale_order_items', 'order_items');
        }

        if (Schema::hasTable('sale_orders')) {
            Schema::rename('sale_orders', 'orders');
        }
    }

    protected function ensureStatusCheck(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $constraint = "{$table}_status_check";

        $exists = DB::select(
            'select 1 from information_schema.table_constraints
             where table_schema = current_schema() and table_name = ? and constraint_name = ?',
            [$table, $constraint]
        );

        if (empty($exists)) {
            DB::statement(
                "alter table {$table} add constraint {$constraint} check (status in ('pending','approved','cancelled'))"
            );
        }
    }

    protected function dropStatusCheck(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::statement("alter table {$table} drop constraint if exists {$table}_status_check");
    }
};
