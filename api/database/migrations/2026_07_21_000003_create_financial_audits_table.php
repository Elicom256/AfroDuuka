<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('business_branch_id')->constrained()->cascadeOnDelete();
            $table->string('audit_number')->unique();
            $table->date('audit_date');
            $table->decimal('expected_balance', 15, 2)->default(0);
            $table->decimal('actual_balance', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'in_progress', 'completed', 'approved', 'cancelled'])->default('draft');
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_audits');
    }
};
