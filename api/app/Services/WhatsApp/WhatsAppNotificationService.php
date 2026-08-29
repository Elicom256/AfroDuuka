<?php

namespace App\Services\WhatsApp;

use App\Jobs\ProcessWhatsAppNotificationJob;
use Illuminate\Support\Str;

class WhatsAppNotificationService
{
    public function buildPayload(array $payload): array
    {
        $businessId = (int) ($payload['business_id'] ?? 0);
        $branchId = $payload['branch_id'] ?? null;
        $type = (string) ($payload['type'] ?? 'general');
        $templateKey = (string) ($payload['template_key'] ?? 'general');
        $recipientPhone = (string) ($payload['recipient_phone'] ?? '');
        $templateData = is_array($payload['template_data'] ?? null) ? $payload['template_data'] : [];

        $dedupeKey = implode(':', [
            'notification',
            $type,
            'business-' . $businessId,
            $branchId ? 'branch-' . $branchId : 'branch-none',
            $templateKey,
            md5(json_encode($templateData, JSON_THROW_ON_ERROR)),
        ]);

        return [
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'type' => $type,
            'template_key' => $templateKey,
            'recipient_phone' => $recipientPhone,
            'template_data' => $templateData,
            'dedupe_key' => $dedupeKey,
            'metadata' => [
                'generated_at' => now()->toDateTimeString(),
                'event_uuid' => (string) Str::uuid(),
            ],
        ];
    }

    public function queueBusinessNotification(array $payload): array
    {
        $normalized = $this->buildPayload($payload);

        ProcessWhatsAppNotificationJob::dispatch($normalized);

        return $normalized;
    }

    public function queueLowStockAlert(array $payload): array
    {
        return $this->queueBusinessNotification([
            'business_id' => $payload['business_id'] ?? 0,
            'branch_id' => $payload['branch_id'] ?? null,
            'type' => 'inventory.low_stock',
            'template_key' => 'inventory.low_stock',
            'recipient_phone' => $payload['recipient_phone'] ?? '',
            'template_data' => [
                'business_name' => $payload['business_name'] ?? 'Your business',
                'product_name' => $payload['product_name'] ?? 'Product',
                'current_stock' => (int) ($payload['current_stock'] ?? 0),
                'reorder_level' => (int) ($payload['reorder_level'] ?? 0),
                'branch_name' => $payload['branch_name'] ?? 'Main branch',
            ],
        ]);
    }

    public function queueOutOfStockAlert(array $payload): array
    {
        return $this->queueBusinessNotification([
            'business_id' => $payload['business_id'] ?? 0,
            'branch_id' => $payload['branch_id'] ?? null,
            'type' => 'inventory.out_of_stock',
            'template_key' => 'inventory.out_of_stock',
            'recipient_phone' => $payload['recipient_phone'] ?? '',
            'template_data' => [
                'business_name' => $payload['business_name'] ?? 'Your business',
                'product_name' => $payload['product_name'] ?? 'Product',
                'current_stock' => (int) ($payload['current_stock'] ?? 0),
                'branch_name' => $payload['branch_name'] ?? 'Main branch',
            ],
        ]);
    }

    public function queueSubscriptionExpiryAlert(array $payload): array
    {
        return $this->queueBusinessNotification([
            'business_id' => $payload['business_id'] ?? 0,
            'branch_id' => $payload['branch_id'] ?? null,
            'type' => 'subscription.expired',
            'template_key' => 'subscription.expired',
            'recipient_phone' => $payload['recipient_phone'] ?? '',
            'template_data' => [
                'business_name' => $payload['business_name'] ?? 'Your business',
                'plan_name' => $payload['plan_name'] ?? 'Your plan',
                'expiry_date' => $payload['expiry_date'] ?? now()->toDateString(),
            ],
        ]);
    }

    public function queueSubscriptionReminderAlert(array $payload): array
    {
        return $this->queueBusinessNotification([
            'business_id' => $payload['business_id'] ?? 0,
            'branch_id' => $payload['branch_id'] ?? null,
            'type' => 'subscription.expiry_reminder',
            'template_key' => 'subscription.expiry_reminder',
            'recipient_phone' => $payload['recipient_phone'] ?? '',
            'template_data' => [
                'business_name' => $payload['business_name'] ?? 'Your business',
                'plan_name' => $payload['plan_name'] ?? 'Your plan',
                'days_remaining' => (int) ($payload['days_remaining'] ?? 2),
            ],
        ]);
    }

    public function queueFreeTrialExpiryAlert(array $payload): array
    {
        return $this->queueBusinessNotification([
            'business_id' => $payload['business_id'] ?? 0,
            'branch_id' => $payload['branch_id'] ?? null,
            'type' => 'subscription.free_trial_expired',
            'template_key' => 'subscription.free_trial_expired',
            'recipient_phone' => $payload['recipient_phone'] ?? '',
            'template_data' => [
                'business_name' => $payload['business_name'] ?? 'Your business',
                'trial_end_date' => $payload['trial_end_date'] ?? now()->toDateString(),
            ],
        ]);
    }

    public function queuePurchaseOrderAlert(array $payload): array
    {
        return $this->queueBusinessNotification([
            'business_id' => $payload['business_id'] ?? 0,
            'branch_id' => $payload['branch_id'] ?? null,
            'type' => 'order.purchase.created',
            'template_key' => 'order.purchase.created',
            'recipient_phone' => $payload['recipient_phone'] ?? '',
            'template_data' => [
                'business_name' => $payload['business_name'] ?? 'Your business',
                'order_number' => $payload['order_number'] ?? 'PO-000000',
                'supplier_name' => $payload['supplier_name'] ?? 'Supplier',
                'total_amount' => $payload['total_amount'] ?? 0,
            ],
        ]);
    }

    public function queueSaleOrderAlert(array $payload): array
    {
        return $this->queueBusinessNotification([
            'business_id' => $payload['business_id'] ?? 0,
            'branch_id' => $payload['branch_id'] ?? null,
            'type' => 'order.sale.created',
            'template_key' => 'order.sale.created',
            'recipient_phone' => $payload['recipient_phone'] ?? '',
            'template_data' => [
                'business_name' => $payload['business_name'] ?? 'Your business',
                'order_number' => $payload['order_number'] ?? 'ORD-000000',
                'customer_name' => $payload['customer_name'] ?? 'Customer',
                'total_amount' => $payload['total_amount'] ?? 0,
            ],
        ]);
    }

    public function queueMonthlyReportAlert(array $payload): array
    {
        return $this->queueBusinessNotification([
            'business_id' => $payload['business_id'] ?? 0,
            'branch_id' => $payload['branch_id'] ?? null,
            'type' => 'report.monthly',
            'template_key' => 'report.monthly',
            'recipient_phone' => $payload['recipient_phone'] ?? '',
            'template_data' => [
                'business_name' => $payload['business_name'] ?? 'Your business',
                'month_name' => $payload['month_name'] ?? now()->monthName,
                'total_sales' => $payload['total_sales'] ?? 0,
                'total_purchases' => $payload['total_purchases'] ?? 0,
                'profit' => $payload['profit'] ?? 0,
            ],
        ]);
    }
}
