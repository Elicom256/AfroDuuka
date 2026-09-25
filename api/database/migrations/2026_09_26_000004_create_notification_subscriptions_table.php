<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->string('email')->unique();

            // Stored hashed, never in the clear: this token appears in the
            // List-Unsubscribe URL that is handed to the mail recipient.
            //
            // Nullable because most rows have no token yet: the first send is what
            // issues one, and an address that has never been mailed is still a
            // legitimate row. A unique index permits any number of NULLs in
            // Postgres, so "at most one row per issued token" still holds. A
            // non-null default of '' would instead make the second new address
            // collide with the first.
            $table->string('token')->nullable()->unique();

            // Categories the address is still subscribed to. Transactional mail is
            // never gated on this row.
            $table->json('categories')->nullable();

            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('resubscribed_at')->nullable();

            $table->timestamps();

            $table->index(['email', 'unsubscribed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_subscriptions');
    }
};
