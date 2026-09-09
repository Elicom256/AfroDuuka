<?php

namespace App\Events\WhatsAppNotificationEvents;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OutOfStockAlert
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $business;
    public $branch;

    public function __construct($product, $business, $branch = null)
    {
        $this->product = $product;
        $this->business = $business;
        $this->branch = $branch;
    }
}
