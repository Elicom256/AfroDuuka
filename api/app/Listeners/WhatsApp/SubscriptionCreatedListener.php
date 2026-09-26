<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class SubscriptionCreatedListener
{
    public function handle(WhatsAppNotificationEvents\SubscriptionCreated $event): void
    {
        (new WhatsAppNotificationService)->queueNewSubscriptionAlert([
            'business_id' => $event->subscription->business_id,
            'branch_id' => null,
            'business_name' => $event->subscription->business?->name ?? 'Your business',
            'plan_name' => $event->subscription->plan?->name ?? 'Your plan',
            'expiry_date' => $event->subscription->ends_at?->format('Y-m-d') ?? now()->addMonth()->format('Y-m-d'),
            'recipient_phone' => $event->subscription->business?->phone,
        ]);
    }
}
