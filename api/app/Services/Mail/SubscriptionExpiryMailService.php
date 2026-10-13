<?php

namespace App\Services\Mail;

use App\Mail\SubscriptionExpiryMail;
use App\Models\Business;
use Illuminate\Support\Facades\Mail;

class SubscriptionExpiryMailService
{
    public function send(Business $business, string $planName, string $endsAt, ?string $renewUrl, array $recipientEmails): void
    {
        foreach ($recipientEmails as $email) {
            Mail::to($email)
                ->send(new SubscriptionExpiryMail($business, $planName, $endsAt, $renewUrl));
        }
    }
}