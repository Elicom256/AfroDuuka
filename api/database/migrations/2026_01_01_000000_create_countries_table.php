<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->char('iso_alpha2', 2)->nullable()->unique(); // For shipping APIs & flag matching
            $table->string('flag_emoji', 4)->nullable();
            $table->char('currency_code', 3);        // ISO 4217 (e.g., UGX, USD)
            $table->string('currency_symbol', 10);   // For frontend formatting (e.g., Shs, $)
            $table->decimal('default_vat_rate', 5, 2)->default(0.00); // For tax calculations
            $table->string('region')->nullable();     // For logistics grouping
            $table->string('calling_code', 10)->nullable(); // For supplier profiles
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
