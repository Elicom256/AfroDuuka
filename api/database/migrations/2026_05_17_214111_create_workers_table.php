<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();

            // core identity
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('employee_code')->unique();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])
                ->default('full_time');
            $table->decimal('salary', 12, 2)->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])
                ->default('active');
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};