<?php

namespace App\Events\WhatsAppNotificationEvents;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;

    public $subscription;

    public function __construct($payment, $subscription)
    {
        $this->payment = $payment;
        $this->subscription = $subscription;
    }
}
