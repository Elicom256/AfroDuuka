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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId("business_id")->constrained()->cascadeOnDelete();
            $table->foreignId("pricing_id")->constrained()->cascadeOnDelete();
            $table->enum("status", ["active", "inactive", "terminated"])->default("active");
            $table->timestamps();

            $table->unique(["business_id", "pricing_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
