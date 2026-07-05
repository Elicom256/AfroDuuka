<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId("business_category_id")->constrained()->cascadeOnDelete();
            $table->foreignId("country_id")->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('address')->nullable()->unique();
            $table->enum("status", ["active", "deactivated", "banned"])->default("active");
            $table->decimal("subscription_balance");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};