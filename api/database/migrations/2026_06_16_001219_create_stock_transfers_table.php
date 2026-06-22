<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_branch_id')->constrained('business_branches')->cascadeOnDelete();
            $table->foreignId('to_branch_id')->constrained('business_branches')->cascadeOnDelete();
            $table->enum('status', ["draft", "in_transit", "received", "cancelled"])->default('draft');
            $table->foreignId('transferred_by')->constrained('users');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('transport_cost', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
