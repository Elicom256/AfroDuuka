<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class PurchaseOrderCreatedListener
{
    public function handle(WhatsAppNotificationEvents\PurchaseOrderCreated $event): void
    {
        (new WhatsAppNotificationService)->queuePurchaseOrderAlert([
            'business_id' => $event->purchaseOrder->business_id,
            'branch_id' => $event->purchaseOrder->branch_id,
            'business_name' => $event->purchaseOrder->business?->name ?? 'Your business',
            'order_number' => $event->purchaseOrder->order_number,
            'supplier_name' => $event->purchaseOrder->supplier?->name ?? 'Supplier',
            'total_amount' => $event->purchaseOrder->total_amount,
            'recipient_phone' => $event->purchaseOrder->business?->phone,
        ]);
    }
}
