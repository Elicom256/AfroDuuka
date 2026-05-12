<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->string('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};