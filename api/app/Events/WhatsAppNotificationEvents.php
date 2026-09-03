<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
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

class SubscriptionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}

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

class SubscriptionExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }
}

class LowStockAlert
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

class PurchaseOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseOrder;

    public function __construct($purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }
}

class SaleOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $saleOrder;

    public function __construct($saleOrder)
    {
        $this->saleOrder = $saleOrder;
    }
}
