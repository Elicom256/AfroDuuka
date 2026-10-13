<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL: add new enum value and update default
        DB::statement("ALTER TYPE businesses_status ADD VALUE IF NOT EXISTS 'pending_verification'");
        DB::statement("ALTER TABLE businesses ALTER COLUMN status SET DEFAULT 'pending_verification'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE businesses ALTER COLUMN status SET DEFAULT 'active'");
        // Note: PostgreSQL doesn't support removing enum values easily
    }
};