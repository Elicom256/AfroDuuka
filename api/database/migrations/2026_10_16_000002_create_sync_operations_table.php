<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable idempotency ledger for the offline sync API.
 *
 * One row per client-generated operation id, unique per business. The unique
 * constraint is the enforcement point for replay safety: a retried batch cannot
 * create a second sale because the second insert of (business_id, operation_id)
 * is refused by PostgreSQL before any business effect is attempted.
 *
 * The row is claimed (`processing`) in its own committed statement, then flipped
 * to `completed` inside the same transaction as the business effects it
 * represents. A crash therefore leaves either `processing` with nothing applied
 * (rollback — safe to re-run) or `completed` with everything applied (replay
 * returns the stored result), never an ambiguous half state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('operation_id');
            $table->string('type', 32);
            $table->string('status', 16)->default('processing');
            $table->string('server_resource_type')->nullable();
            $table->unsignedBigInteger('server_resource_id')->nullable();
            $table->json('result')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            // The client's `created_at` is audit context only (never used for server
            // ordering or financial calculation), so it is stored separately rather
            // than overwriting `created_at` with device time.
            $table->timestamp('client_created_at')->nullable()->after('processed_at');
            $table->timestamps();

            $table->unique(['business_id', 'operation_id']);
            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
    }
};
