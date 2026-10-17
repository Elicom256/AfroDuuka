<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive sync_status columns for user-owned CRUD tables that did not get them
 * with the original eight (sales, sale_items, purchases, purchase_items,
 * stock_movements, products, customers, todos).
 *
 * Same convention as those tables: enum synced|pending|failed, default synced,
 * indexed. The server leaves the default in place. A row is "pending" only in
 * the device outbox until a sync result confirms it — sync_status is not
 * mass-assignable and handlers do not write `pending`.
 *
 * Users are omitted on purpose: that table is the auth identity and must not
 * grow a sync column next to passwords. There is no messages table.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'suppliers',
        'workers',
        'sale_orders',
        'sale_order_items',
        'quotations',
        'quotation_items',
        'expenses',
        'promotions',
        'coupons',
        'sale_returns',
        'sale_return_items',
        'purchase_returns',
        'purchase_return_items',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'sync_status')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->enum('sync_status', ['synced', 'pending', 'failed'])->default('synced')->index();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'sync_status')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('sync_status');
            });
        }
    }
};
