<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_branch_id')->nullable()->constrained('business_branches');
            $table->string('vendor')->nullable();
            $table->text('description')->nullable();
            $table->string('receipt')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->date('payment_date');
            $table->enum('status', ['pending', 'approved', 'cancelled'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'payment_date']);
            $table->index(['business_id', 'expense_category_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
