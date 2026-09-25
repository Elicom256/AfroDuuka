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
            // IANA name. Laravel's Schedule carries a single timezone, so the schedule
            // fires once and each job decides per business whether "07:00 local" has
            // arrived. Without this, 07:00 means 07:00 UTC and a Kampala business gets
            // its monthly report at 10:00.
            $table->string('timezone')->default('Africa/Kampala');
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('address')->nullable()->unique();
            $table->enum("status", ["active", "deactivated", "banned"])->default("active");
            $table->decimal("subscription_balance")->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};