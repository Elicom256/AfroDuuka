<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class SaleOrderCreatedListener
{
    public function handle(WhatsAppNotificationEvents\SaleOrderCreated $event): void
    {
        (new WhatsAppNotificationService)->queueSaleOrderAlert([
            'business_id' => $event->saleOrder->business_id,
            'branch_id' => $event->saleOrder->branch_id,
            'business_name' => $event->saleOrder->business?->name ?? 'Your business',
            'order_number' => $event->saleOrder->order_number,
            'customer_name' => $event->saleOrder->customer?->name ?? 'Customer',
            'total_amount' => $event->saleOrder->total_amount,
            'recipient_phone' => $event->saleOrder->business?->phone,
        ]);
    }
}
