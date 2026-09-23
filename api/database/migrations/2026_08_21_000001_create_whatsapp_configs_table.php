<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whats_app_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('provider')->default('demo');
            $table->string('business_phone')->default('+256731794401');
            $table->string('phone_number_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('message_template')->nullable();
            $table->text('welcome_message')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('whats_app_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->string('locale')->default('en');
            $table->text('body');
            $table->json('variables')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('whats_app_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('recipient');
            $table->string('channel')->default('whatsapp');
            $table->text('message_body');
            $table->json('variables')->nullable();
            $table->string('status')->default('queued');
            $table->json('provider_response')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('dedupe_key')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whats_app_message_logs');
        Schema::dropIfExists('whats_app_templates');
        Schema::dropIfExists('whats_app_configs');
    }
};
