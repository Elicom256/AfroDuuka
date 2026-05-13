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
        Schema::create('business_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId("business_id")->constrained()->cascadeOnDelete()->index();
            $table->string("name")->default("Main Branch");
            $table->string("address")->nullable();

            $table->unique(["business_id", "name"]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_branches');
    }
};
