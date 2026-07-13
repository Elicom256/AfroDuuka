<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->foreignId('sale_return_id')->nullable()->after('sale_id')->constrained('sale_returns')->nullOnDelete();
            $table->foreignId('purchase_return_id')->nullable()->after('purchase_id')->constrained('purchase_returns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->dropForeign(['sale_return_id']);
            $table->dropForeign(['purchase_return_id']);
            $table->dropColumn('sale_return_id');
            $table->dropColumn('purchase_return_id');
        });
    }
};
