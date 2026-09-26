<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};