<?php

namespace App\Services\Mail;

use App\Mail\BusinessVerification;
use App\Models\Business;
use App\Support\FrontendUrl;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BusinessVerificationMailService
{
    /**
     * How long an emailed link stays valid.
     *
     * The email copy promises 24 hours, so this is the value that promise reads from.
     * Change one and you change the other.
     */
    public const TOKEN_TTL_HOURS = 24;

    /**
     * The shortest gap between two sends for the same business.
     *
     * A resend button with no floor is a way to mail a stranger repeatedly, and the
     * "sent" timestamp on the business is what makes the floor possible.
     */
    public const RESEND_COOLDOWN_SECONDS = 60;

    private string $verificationBaseUrl;

    public function __construct(
        string $verificationBaseUrl = '',
    ) {
        // Default to frontend URL, not API URL. The frontend will call the API.
        $this->verificationBaseUrl = $verificationBaseUrl ?: FrontendUrl::path('verify-email');
    }

    /**
     * Issue a token and mail the verification link.
     *
     * Returns the plaintext token — the only copy that will ever exist. What is stored
     * is its hash, so a leaked database row cannot be turned back into a working link.
     * Callers that do not need it (the registration listener) may ignore the return.
     */
    public function send(Business $business): string
    {
        $token = Str::random(64);

        $business->forceFill([
            'email_verification_token' => $this->hashToken($token),
            'email_verification_expires_at' => now()->addHours(self::TOKEN_TTL_HOURS),
            'email_verification_sent_at' => now(),
        ])->save();

        $verificationUrl = $this->verificationBaseUrl.'?token='.$token;

        Mail::to($business->email)
            ->send(new BusinessVerification($business, $verificationUrl));

        return $token;
    }

    /**
     * Resolve a plaintext token to its business, activating it on success.
     *
     * Null means the link is not usable: unknown, already used, or past its expiry.
     * The three are deliberately indistinguishable to the caller, so the response
     * cannot be used to probe which businesses exist or which links were already spent.
     */
    public function verify(string $token): ?Business
    {
        if ($token === '') {
            return null;
        }

        $business = Business::where('email_verification_token', $this->hashToken($token))->first();

        if (! $business) {
            return null;
        }

        // Already verified: hand it back without writing again, so a replayed link is
        // a no-op rather than a second activation.
        if ($business->email_verified_at) {
            return $business;
        }

        // NULL expires_at is treated as "never timed", not "expired". A token issued
        // before this column existed must still be redeemable.
        if ($business->email_verification_expires_at !== null
            && $business->email_verification_expires_at->isPast()) {
            return null;
        }

        $business->forceFill([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_expires_at' => null,
            'status' => 'active',
        ])->save();

        return $business;
    }

    /**
     * Send another link unless the business is verified or still inside the cooldown.
     *
     * Returns the plaintext token when a mail actually went out, null otherwise, so the
     * controller can answer 200 either way without telling a caller whether the address
     * exists or how recently it was mailed.
     */
    public function resend(Business $business): ?string
    {
        if ($business->email_verified_at) {
            return null;
        }

        if (! $this->canResend($business)) {
            return null;
        }

        return $this->send($business);
    }

    /**
     * Whether the cooldown has elapsed since the last send.
     */
    public function canResend(Business $business): bool
    {
        if (! $business->email_verification_sent_at) {
            return true;
        }

        return $this->cooldownRemaining($business) <= 0;
    }

    /**
     * Whole seconds left before another resend is allowed. Zero when eligible.
     */
    public function cooldownRemaining(Business $business): int
    {
        if (! $business->email_verification_sent_at) {
            return 0;
        }

        $elapsed = $business->email_verification_sent_at->diffInSeconds(now());

        return max(0, self::RESEND_COOLDOWN_SECONDS - (int) $elapsed);
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
