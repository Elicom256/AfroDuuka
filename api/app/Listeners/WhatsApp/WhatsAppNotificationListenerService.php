<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class BusinessRegisteredListener
{
    public function handle(WhatsAppNotificationEvents\BusinessRegistered $event): void
    {
        (new WhatsAppNotificationService())->queueBusinessNotification([
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
}

class SubscriptionCreatedListener
{
    public function handle(WhatsAppNotificationEvents\SubscriptionCreated $event): void
    {
        (new WhatsAppNotificationService())->queueNewSubscriptionAlert([
            'business_id' => $event->subscription->business_id,
            'branch_id' => null,
            'business_name' => $event->subscription->business?->name ?? 'Your business',
            'plan_name' => $event->subscription->plan?->name ?? 'Your plan',
            'expiry_date' => $event->subscription->ends_at?->format('Y-m-d') ?? now()->addMonth()->format('Y-m-d'),
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
        ]);
    }
}

class SubscriptionPlanChangedListener
{
    public function handle(WhatsAppNotificationEvents\SubscriptionPlanChanged $event): void
    {
        (new WhatsAppNotificationService())->queuePlanChangeAlert([
            'business_id' => $event->subscription->business_id,
            'branch_id' => null,
            'business_name' => $event->subscription->business?->name ?? 'Your business',
            'old_plan' => $event->oldPlan?->name ?? 'Previous plan',
            'new_plan' => $event->subscription->plan?->name ?? 'New plan',
            'effective_date' => $event->subscription->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
        ]);
    }
}

class PaymentFailedListener
{
    public function handle(WhatsAppNotificationEvents\PaymentFailed $event): void
    {
        (new WhatsAppNotificationService())->queuePaymentFailureAlert([
            'business_id' => $event->subscription->business_id,
            'branch_id' => null,
            'business_name' => $event->subscription->business?->name ?? 'Your business',
            'plan_name' => $event->subscription->plan?->name ?? 'Your plan',
            'amount' => $event->payment->amount_paid,
            'payment_date' => $event->payment->created_at?->format('Y-m-d'),
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
        ]);
    }
}

class SubscriptionExpiredListener
{
    public function handle(WhatsAppNotificationEvents\SubscriptionExpired $event): void
    {
        (new WhatsAppNotificationService())->queueSubscriptionExpiryAlert([
            'business_id' => $event->subscription->business_id,
            'branch_id' => null,
            'business_name' => $event->subscription->business?->name ?? 'Your business',
            'plan_name' => $event->subscription->plan?->name ?? 'Your plan',
            'expiry_date' => $event->subscription->ends_at?->format('Y-m-d'),
            'recipient_phone' => $event->subscription->business?->phone ?? '+256731794401',
        ]);
    }
}

class LowStockAlertListener
{
    public function handle(WhatsAppNotificationEvents\LowStockAlert $event): void
    {
        (new WhatsAppNotificationService())->queueLowStockAlert([
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
}

class OutOfStockAlertListener
{
    public function handle(WhatsAppNotificationEvents\OutOfStockAlert $event): void
    {
        (new WhatsAppNotificationService())->queueOutOfStockAlert([
            'business_id' => $event->business->id,
            'branch_id' => $event->branch?->id,
            'business_name' => $event->business->name,
            'product_name' => $event->product->name,
            'current_stock' => $event->product->quantity,
            'branch_name' => $event->branch?->name ?? 'Main branch',
            'recipient_phone' => $event->business->phone,
        ]);
    }
}

class PurchaseOrderCreatedListener
{
    public function handle(WhatsAppNotificationEvents\PurchaseOrderCreated $event): void
    {
        (new WhatsAppNotificationService())->queuePurchaseOrderAlert([
            'business_id' => $event->purchaseOrder->business_id,
            'branch_id' => $event->purchaseOrder->branch_id,
            'business_name' => $event->purchaseOrder->business?->name ?? 'Your business',
            'order_number' => $event->purchaseOrder->order_number,
            'supplier_name' => $event->purchaseOrder->supplier?->name ?? 'Supplier',
            'total_amount' => $event->purchaseOrder->total_amount,
            'recipient_phone' => $event->purchaseOrder->business?->phone ?? '+256731794401',
        ]);
    }
}

class SaleOrderCreatedListener
{
    public function handle(WhatsAppNotificationEvents\SaleOrderCreated $event): void
    {
        (new WhatsAppNotificationService())->queueSaleOrderAlert([
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
