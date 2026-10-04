<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0)->after('total_amount');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 4)->nullable()->after('unit_price');
            $table->boolean('is_tax_inclusive')->default(false)->after('tax_rate');
            $table->decimal('taxable_amount', 12, 2)->default(0)->after('discount');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('taxable_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'is_tax_inclusive', 'taxable_amount', 'tax_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'tax_amount']);
        });
    }
};
