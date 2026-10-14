<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expiry and send-tracking for business email verification.
 *
 * The verification email has always promised that its link expires in 24 hours, but
 * nothing enforced it: a token stayed valid until somebody used it, so a link that
 * leaked — forwarded mail, a shared inbox, a synced device — activated the business
 * months later. email_verification_expires_at is the claim the email already makes,
 * made true.
 *
 * email_verification_sent_at is not cosmetic either. Resend has no way to tell a
 * double-click from a genuine "it never arrived" click, so without a recorded send
 * time every resend mails again. It is what the cooldown reads.
 *
 * Both nullable: existing rows have never been timed, and a business that is already
 * verified carries neither. NULL expires_at is treated as "no expiry recorded" rather
 * than "expired", so the migration cannot strand a pending verification token that was
 * issued before it ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_token');
            $table->timestamp('email_verification_sent_at')->nullable()->after('email_verification_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['email_verification_expires_at', 'email_verification_sent_at']);
        });
    }
};
