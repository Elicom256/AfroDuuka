<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_branch_product_id')->constrained()->cascadeOnDelete();
            $table->integer('reorder_quantity');
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->boolean('auto_approve')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_branch_id', 'business_branch_product_id'], 'reorder_rule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_rules');
    }
};
