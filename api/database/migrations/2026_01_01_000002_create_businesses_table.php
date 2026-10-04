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
            $table->foreignId('business_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            // IANA name. Laravel's Schedule carries a single timezone, so the schedule
            // fires once and each job decides per business whether "07:00 local" has
            // arrived. Without this, 07:00 means 07:00 UTC and a Kampala business gets
            // its monthly report at 10:00.
            $table->string('timezone')->default('Africa/Kampala');
            $table->string('name');
            // Indexed but deliberately NOT unique. These three columns hold the values a
            // human types about a physical place, and none of them identify a tenant:
            // two shops in one plaza share an address, a trading centre shares a phone
            // number, and "info@" is not a per-business address. The unique indexes
            // that were here made the second business at any shared address fail
            // onboarding with a raw SQLSTATE 23505 instead of a validation message,
            // and they are only safe to drop once email and phone are optional — which
            // they now are.
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('address')->nullable()->index();
            $table->enum('status', ['active', 'deactivated', 'banned'])->default('active');
            $table->string('logo')->nullable();
            $table->decimal('subscription_balance')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
