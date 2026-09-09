<?php

namespace App\Events\WhatsAppNotificationEvents;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusinessRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $business;

    public function __construct($business)
    {
        $this->business = $business;
    }
}
