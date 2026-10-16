<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The client's `created_at` is audit context only (never used for server ordering
 * or financial calculation), which is why it is stored separately from the server's
 * own timestamps rather than overwriting `created_at` with device time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_operations', function (Blueprint $table) {
            $table->timestamp('client_created_at')->nullable()->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('sync_operations', function (Blueprint $table) {
            $table->dropColumn('client_created_at');
        });
    }
};
