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
        Schema::create('employee_remunerations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('type', [ 'salary', 'wage', 'bonus', 'commission', 'allowance', 'deduction', 'advance']);
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->enum('status', ["pending", "paid", "failed"])->default('pending');
            $table->text('description')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_remunerations');
    }
};
