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
        Schema::create('business_branch_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('markup_percentage', 5, 2)->default(0)->after('cost_price');
            $table->integer('reorder_level')->default(0);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ["active", "inactive", "damaged", "out_of_stock", "discontinued"])->default("active");

            $table->timestamps();
            // $table->unique(['business_branch_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_branch_products');
    }
};