<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_taxes', function (Blueprint $table) {
            $table->id("id");
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            // $table->foreignId('business_branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->decimal('rate', 12, 2)->default(0);
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ["active", "inactive"])->default('active');

            $table->timestamps();
            $table->unique(["business_id", "name"]);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists("employee_remunerations");
    }
};
