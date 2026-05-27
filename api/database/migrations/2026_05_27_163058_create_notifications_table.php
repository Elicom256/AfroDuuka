<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            // Who receives the notification
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            
            // Notification type/category
            $table->string('type');                    // e.g. 'low_stock', 'new_sale', 'new_purchase', 'payment_received'
            $table->string('title');
            $table->text('message');
            
            // Optional: Link to related record
            $table->string('notifiable_type')->nullable();  // e.g. 'App\Models\Sale'
            $table->unsignedBigInteger('notifiable_id')->nullable();
            
            // Extra data (JSON)
            $table->json('data')->nullable();
            
            // Status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'is_read']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};