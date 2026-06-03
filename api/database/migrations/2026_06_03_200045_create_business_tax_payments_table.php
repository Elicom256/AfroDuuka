<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_tax_payments', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('business_branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_tax_id')->constrained()->cascadeOnDelete();

            // Core payment data
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);           // Support partial payments
            $table->decimal('balance', 15, 2)->default(0.00);               // amount - paid_amount

            // Tax period & timing (Very important for enterprises)
            $table->string('tax_period');                                   // e.g., '2026-Q1', '2026', 'FY2026'
            $table->date('due_date');
            $table->date('payment_date')->nullable();                       // When they actually paid
            $table->timestamp('paid_at')->nullable();

            // Status
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue', 'waived', 'refunded' ])->default('unpaid');

            // Audit & Tracking
            $table->string('reference_number')->unique()->nullable();       // Receipt / Transaction ref
            $table->string('payment_method')->nullable();                   // bank_transfer, mpesa, cheque, etc.
            $table->json('payment_metadata')->nullable();                   // gateway response, etc.
            $table->text('notes')->nullable();

            // Who recorded this
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Recommended for enterprise (audit trail)

            // Indexes for performance
            $table->index(['business_branch_id', 'tax_period']);
            $table->index(['status', 'due_date']);
            $table->index('payment_date');
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_tax_payments');
    }
};