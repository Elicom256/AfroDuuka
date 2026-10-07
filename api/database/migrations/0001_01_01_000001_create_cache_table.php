<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The original version of this migration failed in production on its
     * ALTER (SQLSTATE 25P02 from a dirty pooled connection) and could leave
     * the cache tables behind without their primary keys, after which every
     * container boot re-ran it and crashed under `set -e`. Create what is
     * missing, repair what is incomplete, and never touch what is already
     * correct.
     */
    public function up(): void
    {
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->bigInteger('expiration')->index();
            });
        } elseif (! $this->hasPrimaryKey('cache')) {
            // Without a unique index, Cache's insertOrIgnore never conflicts,
            // so duplicate keys may have accumulated. Remove them before the
            // primary key can be added.
            DB::statement('delete from cache a using cache b where a.ctid > b.ctid and a.key = b.key');
            DB::statement('alter table "cache" add primary key ("key")');
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->bigInteger('expiration')->index();
            });
        } elseif (! $this->hasPrimaryKey('cache_locks')) {
            DB::statement('delete from cache_locks a using cache_locks b where a.ctid > b.ctid and a.key = b.key');
            DB::statement('alter table "cache_locks" add primary key ("key")');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }

    protected function hasPrimaryKey(string $table): bool
    {
        return DB::table('information_schema.table_constraints')
            ->whereRaw('table_schema = current_schema()')
            ->where('table_name', $table)
            ->where('constraint_type', 'PRIMARY KEY')
            ->exists();
    }
};
