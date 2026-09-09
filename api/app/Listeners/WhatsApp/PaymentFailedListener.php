<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class PaymentFailedListener
{
    public function handle(WhatsAppNotificationEvents\PaymentFailed $event): void
    {
        (new WhatsAppNotificationService())->queuePaymentFailureAlert([
            'business_id' => $event->subscription->business_id,
            'branch_id' => null,
            'business_name' => $event->subscription->business?->name ?? 'Your business',
            'plan_name' => $event->subscription->plan?->name ?? 'Your plan',
            'amount' => $event->payment->amount_paid,
            'payment_date' => $event->payment->created_at?->format('Y-m-d'),
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
        ]);
    }
}
