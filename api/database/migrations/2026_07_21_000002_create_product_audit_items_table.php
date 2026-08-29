<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('system_quantity')->default(0);
            $table->integer('counted_quantity')->default(0);
            $table->integer('difference')->default(0);
            $table->integer('adjustment_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_audit_items');
    }
};
