<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class LowStockAlertListener
{
    public function handle(WhatsAppNotificationEvents\LowStockAlert $event): void
    {
        (new WhatsAppNotificationService)->queueLowStockAlert([
            'business_id' => $event->business->id,
            'branch_id' => $event->branch?->id,
            'business_name' => $event->business->name,
            'product_name' => $event->product->name,
            'current_stock' => $event->product->quantity,
            'reorder_level' => $event->product->reorder_level,
            'branch_name' => $event->branch?->name ?? 'Main branch',
            'recipient_phone' => $event->business->phone,
        ]);
    }
}
