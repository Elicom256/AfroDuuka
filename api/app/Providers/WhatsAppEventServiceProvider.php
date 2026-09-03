<?php

namespace App\Providers;

use App\Listeners\WhatsApp\BusinessRegisteredListener;
use App\Listeners\WhatsApp\SubscriptionCreatedListener;
use App\Listeners\WhatsApp\SubscriptionPlanChangedListener;
use App\Listeners\WhatsApp\PaymentFailedListener;
use App\Listeners\WhatsApp\SubscriptionExpiredListener;
use App\Listeners\WhatsApp\LowStockAlertListener;
use App\Listeners\WhatsApp\OutOfStockAlertListener;
use App\Listeners\WhatsApp\PurchaseOrderCreatedListener;
use App\Listeners\WhatsApp\SaleOrderCreatedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class WhatsAppEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        WhatsAppNotificationEvents\BusinessRegistered::class => [
            BusinessRegisteredListener::class,
        ],
        WhatsAppNotificationEvents\SubscriptionCreated::class => [
            SubscriptionCreatedListener::class,
        ],
        WhatsAppNotificationEvents\SubscriptionPlanChanged::class => [
            SubscriptionPlanChangedListener::class,
        ],
        WhatsAppNotificationEvents\PaymentFailed::class => [
            PaymentFailedListener::class,
        ],
        WhatsAppNotificationEvents\SubscriptionExpired::class => [
            SubscriptionExpiredListener::class,
        ],
        WhatsAppNotificationEvents\LowStockAlert::class => [
            LowStockAlertListener::class,
        ],
        WhatsAppNotificationEvents\OutOfStockAlert::class => [
            OutOfStockAlertListener::class,
        ],
        WhatsAppNotificationEvents\PurchaseOrderCreated::class => [
            PurchaseOrderCreatedListener::class,
        ],
        WhatsAppNotificationEvents\SaleOrderCreated::class => [
            SaleOrderCreatedListener::class,
        ],
    ];

    public function boot(): void
    {
        $this->registerListeners($this->listen);
    }
}