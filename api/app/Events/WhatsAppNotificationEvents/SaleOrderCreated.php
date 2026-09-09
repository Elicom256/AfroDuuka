<?php

namespace App\Events\WhatsAppNotificationEvents;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $saleOrder;

    public function __construct($saleOrder)
    {
        $this->saleOrder = $saleOrder;
    }
}
