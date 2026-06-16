<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('points_balance', 12, 2)->default(0);
            $table->string('tier')->default('bronze'); // bronze, silver, gold, platinum
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['loyalty_program_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_cards');
    }
};
