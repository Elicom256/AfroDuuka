<?php

/*-----------------------------------------------------------------------------------
 * Migration: Create price_histories table
 * -------------------------------
 * Tracks every product price change automatically.
 * Never overwrites — each price change is a new record.
 *---------------------------------------------------------------------------------*/

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
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained("users")->cascadeOnDelete();
            $table->decimal('old_cost_price', 12, 2)->nullable();
            $table->decimal('new_cost_price', 12, 2)->nullable();
            $table->decimal('old_sale_price', 12, 2)->nullable();
            $table->decimal('new_sale_price', 12, 2)->nullable();
            $table->string('change_reason')->nullable();            // optional reason for the change
            $table->timestamps();                                   // created_at = when change happened
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
