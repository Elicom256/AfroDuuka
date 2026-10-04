<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->foreignId('quotation_id')->nullable()->after('order_number')
                ->constrained('quotations')->nullOnDelete();
        });

        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->unsignedInteger('allocated_qty')->default(0)->after('quantity');
            $table->unsignedInteger('shipped_qty')->default(0)->after('allocated_qty');
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->dropColumn(['allocated_qty', 'shipped_qty']);
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quotation_id');
        });
    }
};
