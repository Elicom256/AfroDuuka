<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description')->nullable();
            $table->string('event')->nullable()->after('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('business_branch_id')->nullable()->constrained('business_branches')->cascadeOnDelete();
            $table->string('action')->nullable();
            $table->text('metadata')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index(['business_id', 'created_at']);
            $table->index(['business_branch_id', 'created_at']);
            $table->index(['action', 'created_at']);

            $table->index(['subject_type', 'subject_id']);
            $table->index(['log_name', 'created_at']);
            $table->index(['causer_id', 'created_at']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
