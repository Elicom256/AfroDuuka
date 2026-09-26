<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class OutOfStockAlertListener
{
    public function handle(WhatsAppNotificationEvents\OutOfStockAlert $event): void
    {
        (new WhatsAppNotificationService)->queueOutOfStockAlert([
            'business_id' => $event->business->id,
            'branch_id' => $event->branch?->id,
            'business_name' => $event->business->name,
            'product_name' => $event->product->name,
            'current_stock' => $event->product->quantity,
            'branch_name' => $event->branch?->name ?? 'Main branch',
            'recipient_phone' => $event->business->phone,
        ]);
    }
}
