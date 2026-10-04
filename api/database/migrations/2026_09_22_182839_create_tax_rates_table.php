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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_category_id')->constrained('tax_categories')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 5, 4);
            $table->string('jurisdiction_zone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tax_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
