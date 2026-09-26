<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class FreeTrialExpiredListener
{
    public function handle(WhatsAppNotificationEvents\FreeTrialExpired $event): void
    {
        (new WhatsAppNotificationService)->queueFreeTrialExpiryAlert([
            'business_id' => $event->subscription->business_id,
            'branch_id' => null,
            'business_name' => $event->subscription->business?->name ?? 'Your business',
            'trial_end_date' => $event->subscription->trial_ends_at?->toDateString() ?? now()->toDateString(),
            'recipient_phone' => $event->subscription->business?->phone,
        ]);
    }
}
