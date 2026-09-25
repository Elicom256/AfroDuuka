<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();

            // Deliberately named business_branch_id, not branch_id: BaseModel and
            // EffectiveBranchScope both key off this exact column name. A branch-level
            // recipient that used another name would silently escape branch scoping.
            $table->foreignId('business_branch_id')->nullable()->constrained('business_branches')->cascadeOnDelete();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // owner | admin | manager | branch_manager | custom
            $table->string('label')->default('owner');

            // whatsapp | email
            $table->string('channel');

            // E.164 for whatsapp, email address for email. Normalised on write.
            $table->string('address');

            // Per-category opt in/out. Mandatory categories are always on regardless.
            $table->json('categories')->nullable();

            $table->boolean('is_active')->default(true);

            // An unverified recipient is treated as absent, and the attempt is logged.
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'channel']);
            $table->index(['business_id', 'business_branch_id']);
        });

        // Postgres treats NULLs as distinct, so a single composite unique index would
        // not stop duplicate business-level recipients. Two partial indexes cover both
        // the branch-level and the business-level case.
        DB::statement(
            'CREATE UNIQUE INDEX notification_recipients_branch_level_unique
             ON notification_recipients (business_id, business_branch_id, channel, address)
             WHERE business_branch_id IS NOT NULL'
        );

        DB::statement(
            'CREATE UNIQUE INDEX notification_recipients_business_level_unique
             ON notification_recipients (business_id, channel, address)
             WHERE business_branch_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};
