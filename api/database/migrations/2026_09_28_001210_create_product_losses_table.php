<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_losses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_movement_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('type', ['damaged', 'expired', 'lost']);
            $table->integer('quantity');
            $table->unsignedInteger('unit_cost');
            $table->unsignedInteger('total_loss');
            $table->text('reason')->nullable();
            $table->date('loss_date');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_branch_id', 'type', 'loss_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_losses');
    }
};
