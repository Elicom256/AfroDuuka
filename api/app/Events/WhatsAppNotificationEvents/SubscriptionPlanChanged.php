<?php

namespace App\Events\WhatsAppNotificationEvents;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPlanChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscription;

    public $oldPlan;

    public function __construct($subscription, $oldPlan)
    {
        $this->subscription = $subscription;
        $this->oldPlan = $oldPlan;
    }
}
