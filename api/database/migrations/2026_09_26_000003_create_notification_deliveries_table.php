<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();

            // See notification_recipients: the column name is load-bearing for branch
            // scoping, so it must stay business_branch_id.
            $table->foreignId('business_branch_id')->nullable()->constrained('business_branches')->cascadeOnDelete();

            // whatsapp | email
            $table->string('channel');

            // The preference bucket: subscription | payment | security | inventory |
            // order | report | system
            $table->string('category');

            // The specific event, e.g. subscription.renewed. Carrying the type on the
            // row is what prevents the "filter on a column that does not exist" class
            // of bug that killed the previous lifecycle jobs.
            $table->string('type');

            // Equal to the notification type in practice; kept separate so the rendered
            // template can be traced without inferring it.
            $table->string('template_key')->nullable();

            $table->foreignId('recipient_id')->nullable()->constrained('notification_recipients')->nullOnDelete();

            // Denormalised snapshot: numbers and emails change, the historical record
            // of where a message actually went must not.
            //
            // Nullable because this table also records sends that never happened. A
            // no_recipient suppression has no address to snapshot, and that row is the
            // whole reason the table exists. A placeholder string here would defeat the
            // purpose of the column, which is to be a truthful record of the target.
            $table->string('recipient_address')->nullable();

            // pending | sending | sent | delivered | read | failed | suppressed
            $table->string('status')->default('pending');

            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();

            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();

            // The rendered parameters, so a send can be replayed or debugged without
            // re-rendering the template.
            $table->json('payload')->nullable();

            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('suppressed_at')->nullable();

            // The dedupe mechanism itself. A reservation that collides here is silently
            // dropped, which makes duplicate suppression race-proof rather than
            // dependent on a check-then-send SELECT.
            //
            // Uniqueness is on (dedupe_key, channel), not on dedupe_key alone. Eight of
            // the catalogue notifications are "E + W" and therefore produce two delivery
            // rows for one event, so a unique key on its own would let whichever channel
            // reserved first suppress the other every single time. The key stays the
            // event's identity exactly as documented; the channel is the transport, and
            // is part of what makes a delivery unique rather than bolted onto the key.
            $table->string('dedupe_key');

            // Kept so a send rejected by Meta can be diagnosed without guessing which
            // approved template was actually used.
            $table->string('meta_template_name')->nullable();
            $table->string('meta_template_language')->nullable();

            // Transactional: bypasses preferences and cannot be opted out of.
            $table->boolean('is_mandatory')->default(false);

            // Why a row was suppressed without being sent, e.g. no_recipient,
            // template_not_approved, opted_out, address_suppressed.
            $table->string('suppressed_reason')->nullable();

            $table->timestamps();

            // The webhook joins on this pair to reconcile delivery status.
            $table->index(['provider', 'provider_message_id']);
            $table->index(['business_id', 'created_at']);
            $table->index(['channel', 'status']);
            $table->index(['business_id', 'type']);
            $table->unique(['dedupe_key', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
