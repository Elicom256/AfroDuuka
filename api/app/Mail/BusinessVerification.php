<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BusinessVerification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly string $verificationUrl,
    ) {}

    public function build(): static
    {
        return $this->subject('Verify your email address - ' . config('app.name'))
            ->view('mail.business.verification', [
                'business' => $this->business,
                'verificationUrl' => $this->verificationUrl,
            ]);
    }
}