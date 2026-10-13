<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly string $planName,
        public readonly string $endsAt,
        public readonly ?string $renewUrl = null,
    ) {}

    public function build(): static
    {
        return $this->subject('Your subscription is expiring - ' . $this->planName)
            ->view('mail.subscription.expiry', [
                'business' => $this->business,
                'planName' => $this->planName,
                'endsAt' => $this->endsAt,
                'renewUrl' => $this->renewUrl,
            ]);
    }
}