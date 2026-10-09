<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('period');
            $table->enum('status', ['active', 'inactive']);
            $table->foreignId('set_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('salaries', function (Blueprint $table) {
            $table->index(['business_id', 'business_branch_id']);
            $table->index(['role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
