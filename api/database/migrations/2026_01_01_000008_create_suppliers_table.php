<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('supplier_code')->unique();     // internal code/ID
            $table->string('company_name')->nullable();    // if customer is a company
            $table->enum('status', ["active", "suspended"])->default("active");
            $table->foreignId('business_id')->nullable()->index()->constrained('businesses')->nullOnDelete();
            $table->foreignId('business_branch_id')->nullable()->index()->constrained('business_branches')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};