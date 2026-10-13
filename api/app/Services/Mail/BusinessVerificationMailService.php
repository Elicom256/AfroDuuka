<?php

namespace App\Services\Mail;

use App\Mail\BusinessVerification;
use App\Models\Business;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BusinessVerificationMailService
{
    private string $verificationBaseUrl;

    public function __construct(
        string $verificationBaseUrl = '',
    ) {
        // Default to frontend URL, not API URL. The frontend will call the API.
        $this->verificationBaseUrl = $verificationBaseUrl ?: config('app.frontend_url', config('app.url')) . '/verify-email';
    }

    public function send(Business $business): void
    {
        $token = $this->generateToken($business);
        $verificationUrl = $this->verificationBaseUrl . '?token=' . $token;

        Mail::to($business->email)
            ->send(new BusinessVerification($business, $verificationUrl));
    }

    public function verify(string $token): ?Business
    {
        $business = Business::where('email_verification_token', $token)->first();

        if (! $business) {
            return null;
        }

        if ($business->email_verified_at) {
            return $business; // Already verified
        }

        $business->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'status' => 'active',
        ]);

        return $business;
    }

    private function generateToken(Business $business): string
    {
        $token = Str::random(64);

        $business->update(['email_verification_token' => $token]);

        return $token;
    }

    public function resend(Business $business): void
    {
        if ($business->email_verified_at) {
            return; // Already verified
        }

        $this->send($business);
    }
}