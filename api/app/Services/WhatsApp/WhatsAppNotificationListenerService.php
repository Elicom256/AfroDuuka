<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService as WhatsAppService;

class BusinessRegisteredListener
{
    public function handle(WhatsAppNotificationEvents\BusinessRegistered $event): void
    {
        (new WhatsAppService())->handleBusinessRegistered($event);
    }
}

class SubscriptionCreatedListener
{
    public function handle(WhatsAppNotificationEvents\SubscriptionCreated $event): void
    {
        (new WhatsAppService())->handleSubscriptionCreated($event);
    }
}

class SubscriptionPlanChangedListener
{
    public function handle(WhatsAppNotificationEvents\SubscriptionPlanChanged $event): void
    {
        (new WhatsAppService())->handleSubscriptionPlanChanged($event);
    }
}

class PaymentFailedListener
{
    public function handle(WhatsAppNotificationEvents\PaymentFailed $event): void
    {
        (new WhatsAppService())->handlePaymentFailed($event);
    }
}

class SubscriptionExpiredListener
{
    public function handle(WhatsAppNotificationEvents\SubscriptionExpired $event): void
    {
        (new WhatsAppService())->handleSubscriptionExpired($event);
    }
}

class LowStockAlertListener
{
    public function handle(WhatsAppNotificationEvents\LowStockAlert $event): void
    {
        (new WhatsAppService())->handleLowStockAlert($event);
    }
}

class OutOfStockAlertListener
{
    public function handle(WhatsAppNotificationEvents\OutOfStockAlert $event): void
    {
        (new WhatsAppService())->handleOutOfStockAlert($event);
    }
}

class PurchaseOrderCreatedListener
{
    public function handle(WhatsAppNotificationEvents\PurchaseOrderCreated $event): void
    {
        (new WhatsAppService())->handlePurchaseOrderCreated($event);
    }
}

class SaleOrderCreatedListener
{
    public function handle(WhatsAppNotificationEvents\SaleOrderCreated $event): void
    {
        (new WhatsAppService())->handleSaleOrderCreated($event);
    }
}

class WhatsAppNotificationService
{
    public function handleBusinessRegistered(WhatsAppNotificationEvents\BusinessRegistered $event): void
    {
        $service = new WhatsAppNotificationService();
        $service->queueBusinessNotification([
            'business_id' => $event->business->id,
            'type' => 'registration',
            'template_key' => 'registration.welcome',
            'recipient_phone' => $event->business->phone,
            'template_data' => [
                'business_name' => $event->business->name,
                'phone' => $event->business->phone,
            ],
        ]);
    }

    public function handleSubscriptionCreated(WhatsAppNotificationEvents\SubscriptionCreated $event): void
    {
        $service = new WhatsAppNotificationService();
        $plan = $event->subscription->plan;

        $service->queueBusinessNotification([
            'business_id' => $event->subscription->business_id,
            'type' => 'subscription.created',
            'template_key' => 'subscription.created',
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
            'template_data' => [
                'business_name' => $event->subscription->business?->name ?? 'Your business',
                'plan_name' => $plan?->name ?? 'Your plan',
                'expiry_date' => $event->subscription->ends_at?->format('Y-m-d') ?? now()->addMonth()->format('Y-m-d'),
            ],
        ]);
    }

    public function handleSubscriptionPlanChanged(WhatsAppNotificationEvents\SubscriptionPlanChanged $event): void
    {
        $service = new WhatsAppNotificationService();
        $plan = $event->subscription->plan;

        $service->queueBusinessNotification([
            'business_id' => $event->subscription->business_id,
            'type' => 'subscription.plan_changed',
            'template_key' => 'subscription.plan_changed',
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
            'template_data' => [
                'business_name' => $event->subscription->business?->name ?? 'Your business',
                'old_plan' => $event->oldPlan?->name ?? 'Previous plan',
                'new_plan' => $plan?->name ?? 'New plan',
                'effective_date' => $event->subscription->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            ],
        ]);
    }

    public function handlePaymentFailed(WhatsAppNotificationEvents\PaymentFailed $event): void
    {
        $service = new WhatsAppNotificationService();
        $plan = $event->subscription->plan;

        $service->queueBusinessNotification([
            'business_id' => $event->subscription->business_id,
            'type' => 'payment.failed',
            'template_key' => 'payment.failed',
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
            'template_data' => [
                'business_name' => $event->subscription->business?->name ?? 'Your business',
                'amount' => $event->payment->amount_paid,
                'payment_date' => $event->payment->created_at?->format('Y-m-d'),
                'plan_name' => $plan?->name ?? 'Your plan',
            ],
        ]);
    }

    public function handleSubscriptionExpired(WhatsAppNotificationEvents\SubscriptionExpired $event): void
    {
        $service = new WhatsAppNotificationService();
        $plan = $event->subscription->plan;

        $service->queueSubscriptionExpiryAlert([
            'business_id' => $event->subscription->business_id,
            'branch_id' => null,
            'business_name' => $event->subscription->business?->name ?? 'Your business',
            'plan_name' => $plan?->name ?? 'Your plan',
            'expiry_date' => $event->subscription->ends_at?->format('Y-m-d'),
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
        ]);
    }

    public function handleLowStockAlert(WhatsAppNotificationEvents\LowStockAlert $event): void
    {
        $service = new WhatsAppNotificationService();
        $service->queueLowStockAlert([
            'business_id' => $event->business->id,
            'branch_id' => $event->branch?->id,
            'business_name' => $event->business->name,
            'product_name' => $event->product->name,
            'current_stock' => $event->product->quantity,
            'reorder_level' => $event->product->reorder_level,
            'branch_name' => $event->branch?->name ?? 'Main branch',
            'recipient_phone' => $event->business->phone,
        ]);
    }

    public function handleOutOfStockAlert(WhatsAppNotificationEvents\OutOfStockAlert $event): void
    {
        $service = new WhatsAppNotificationService();
        $service->queueOutOfStockAlert([
            'business_id' => $event->business->id,
            'branch_id' => $event->branch?->id,
            'business_name' => $event->business->name,
            'product_name' => $event->product->name,
            'current_stock' => $event->product->quantity,
            'branch_name' => $event->branch?->name ?? 'Main branch',
            'recipient_phone' => $event->business->phone,
        ]);
    }

    public function handlePurchaseOrderCreated(WhatsAppNotificationEvents\PurchaseOrderCreated $event): void
    {
        $service = new WhatsAppNotificationService();
        $service->queuePurchaseOrderAlert([
            'business_id' => $event->purchaseOrder->business_id,
            'branch_id' => $event->purchaseOrder->branch_id,
            'business_name' => $event->purchaseOrder->business?->name ?? 'Your business',
            'order_number' => $event->purchaseOrder->order_number,
            'supplier_name' => $event->purchaseOrder->supplier?->name ?? 'Supplier',
            'total_amount' => $event->purchaseOrder->total_amount,
            'recipient_phone' => $event->purchaseOrder->business?->phone ?? '+256731794401',
        ]);
    }

    public function handleSaleOrderCreated(WhatsAppNotificationEvents\SaleOrderCreated $event): void
    {
        $service = new WhatsAppNotificationService();
        $service->queueSaleOrderAlert([
            'business_id' => $event->saleOrder->business_id,
            'branch_id' => $event->saleOrder->branch_id,
            'business_name' => $event->saleOrder->business?->name ?? 'Your business',
            'order_number' => $event->saleOrder->order_number,
            'customer_name' => $event->saleOrder->customer?->name ?? 'Customer',
            'total_amount' => $event->saleOrder->total_amount,
            'recipient_phone' => $event->saleOrder->business?->phone ?? '+256731794401',
        ]);
    }
}
