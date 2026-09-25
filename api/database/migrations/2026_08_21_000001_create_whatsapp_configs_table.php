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
            // Unique: the service resolves a config with updateOrCreate(['business_id' => ...]),
            // which has nothing to protect it without this. Concurrent requests would
            // otherwise insert duplicates, after which ->first() becomes non-deterministic
            // and a business can send from the wrong phone number.
            $table->foreignId('business_id')->unique()->constrained('businesses')->cascadeOnDelete();
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
            // name is our template_key, resolved exactly. Do not overload `category`
            // for this: subscription.created, order.purchase.created and
            // order.sale.created all end in "created", so a suffix or category match
            // mis-routes one notification into another's template.
            $table->string('name');
            $table->string('category');
            $table->string('locale')->default('en');
            $table->text('body');
            // The variable NAMES the body expects, not values. Never render from this.
            $table->json('variables')->nullable();
            $table->string('status')->default('pending');

            // The Meta side of the template, kept separate from our wording above.
            // provider_name/language_code are not user-editable: a mismatch between
            // them and the approved template fails the send at Meta, not here.
            $table->string('provider_name')->nullable();
            $table->string('language_code')->nullable();
            // UTILITY | MARKETING | AUTHENTICATION. Most of these are UTILITY, which is
            // the cheap tier with far more generous limits.
            $table->string('template_category')->nullable();
            // Mirror of Meta's status: APPROVED | PENDING | REJECTED. A notification
            // whose template is not APPROVED is not dispatched.
            $table->string('template_status')->default('pending');
            // NAMED | POSITIONAL. Must match the approved template exactly, along with
            // the number of {{n}} placeholders, or sends fail on a parameter mismatch.
            $table->string('parameter_format')->default('NAMED');
            // Transactional: not user-editable and not opt-out-able.
            $table->boolean('is_mandatory')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'name', 'locale']);
            $table->index(['provider_name', 'language_code']);
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
