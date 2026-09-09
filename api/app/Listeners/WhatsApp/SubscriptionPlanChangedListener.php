<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class SubscriptionPlanChangedListener
{
    public function handle(WhatsAppNotificationEvents\SubscriptionPlanChanged $event): void
    {
        (new WhatsAppNotificationService())->queuePlanChangeAlert([
            'business_id' => $event->subscription->business_id,
            'branch_id' => null,
            'business_name' => $event->subscription->business?->name ?? 'Your business',
            'old_plan' => $event->oldPlan?->name ?? 'Previous plan',
            'new_plan' => $event->subscription->plan?->name ?? 'New plan',
            'effective_date' => $event->subscription->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
        ]);
    }
}
