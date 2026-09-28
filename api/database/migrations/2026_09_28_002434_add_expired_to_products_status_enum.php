<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE products DROP CONSTRAINT products_status_check");
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_status_check CHECK (status IN ('active', 'inactive', 'damaged', 'expired', 'out_of_stock', 'discontinued'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products DROP CONSTRAINT products_status_check");
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_status_check CHECK (status IN ('active', 'inactive', 'damaged', 'out_of_stock', 'discontinued'))");
    }
};
