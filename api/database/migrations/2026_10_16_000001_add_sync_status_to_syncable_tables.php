<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `sync_status` marker to every table whose rows can originate on a device and
 * be synchronised later.
 *
 * The create migrations for these tables were also edited to include the column, but
 * that only helps a fresh install: an already-migrated database never re-runs a create
 * migration, so without this file the column would be missing in every deployed
 * environment. The `hasColumn` guard is what lets both worlds run the same migration.
 *
 * `synced` is the default because a row written through the normal online API is
 * immediately server-authoritative. A row pushed from a device's outbox is inserted
 * with `pending` explicitly and flipped to `synced` once the server has applied and
 * confirmed it.
 */
return new class extends Migration
{
    private array $tables = [
        'sales',
        'sale_items',
        'products',
        'stock_movements',
        'customers',
        'purchases',
        'purchase_items',
        'todos',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'sync_status')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->enum('sync_status', ['synced', 'pending', 'failed'])->default('synced')->index();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sync_status')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('sync_status');
            });
        }
    }
};
