<?php

namespace App\Events\WhatsAppNotificationEvents;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseOrder;

    public function __construct($purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }
}
