<?php

namespace Tests\Unit\WhatsApp;

use App\Jobs\ProcessWhatsAppNotificationJob;
use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppTemplateService;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhaseOneMessagingTest extends TestCase
{
    #[Test]
    public function it_renders_template_variables_for_a_whatsapp_message(): void
    {
        $service = new WhatsAppTemplateService();

        $rendered = $service->render('Hello {{business_name}}, your stock alert for {{product_name}} is at {{current_stock}}.', [
            'business_name' => 'DuukaFlow Demo',
            'product_name' => 'Rice',
            'current_stock' => 4,
        ]);

        $this->assertSame('Hello DuukaFlow Demo, your stock alert for Rice is at 4.', $rendered);
    }

    #[Test]
    public function it_builds_a_notification_payload_with_a_dedupe_key(): void
    {
        $service = new WhatsAppNotificationService();

        $payload = $service->buildPayload([
            'business_id' => 12,
            'branch_id' => 5,
            'type' => 'low_stock',
            'template_key' => 'inventory.low_stock',
            'recipient_phone' => '+256712345678',
            'template_data' => [
                'product_name' => 'Rice',
                'current_stock' => 4,
                'threshold' => 5,
            ],
        ]);

        $this->assertSame('low_stock', $payload['type']);
        $this->assertSame('+256712345678', $payload['recipient_phone']);
        $this->assertStringContainsString('low_stock', $payload['dedupe_key']);
        $this->assertStringContainsString('business-12', $payload['dedupe_key']);
    }

    #[Test]
    public function it_can_send_a_demo_notification_with_the_provider_factory(): void
    {
        $provider = WhatsAppProviderFactory::create([
            'provider' => 'demo',
            'business_phone' => '+256731794401',
            'access_token' => 'demo_access_token',
        ]);

        $result = $provider->sendMessage([
            'to' => '+256712345678',
            'message' => 'Hello DuukaFlow Demo, your stock alert for Rice is at 4.',
            'template_key' => 'inventory.low_stock',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('demo', $result['provider']);
        $this->assertSame('Hello DuukaFlow Demo, your stock alert for Rice is at 4.', $result['message']);
    }

    #[Test]
    public function it_dispatches_a_registration_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queueBusinessNotification([
            'business_id' => 99,
            'type' => 'registration',
            'template_key' => 'registration.welcome',
            'recipient_phone' => '+256712345678',
            'template_data' => ['business_name' => 'DuukaFlow Demo'],
        ]);

        $this->assertSame('registration', $payload['type']);
        $this->assertStringContainsString('registration', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['dedupe_key'] === $payload['dedupe_key'];
        });
    }

    #[Test]
    public function it_dispatches_a_subscription_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queueBusinessNotification([
            'business_id' => 99,
            'type' => 'subscription.created',
            'template_key' => 'subscription.created',
            'recipient_phone' => '+256712345678',
            'template_data' => [
                'business_name' => 'DuukaFlow Demo',
                'plan_name' => 'Starter',
                'expiry_date' => '2026-09-01',
            ],
        ]);

        $this->assertSame('subscription.created', $payload['type']);
        $this->assertStringContainsString('subscription.created', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['type'] === $payload['type'];
        });
    }

    #[Test]
    public function it_dispatches_a_low_stock_inventory_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queueLowStockAlert([
            'business_id' => 12,
            'branch_id' => 3,
            'product_name' => 'Rice',
            'current_stock' => 4,
            'reorder_level' => 5,
            'recipient_phone' => '+256712345678',
        ]);

        $this->assertSame('inventory.low_stock', $payload['type']);
        $this->assertSame('Rice', $payload['template_data']['product_name']);
        $this->assertStringContainsString('inventory.low_stock', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['type'] === $payload['type'];
        });
    }

    #[Test]
    public function it_dispatches_an_out_of_stock_inventory_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queueOutOfStockAlert([
            'business_id' => 12,
            'branch_id' => 3,
            'product_name' => 'Rice',
            'current_stock' => 0,
            'recipient_phone' => '+256712345678',
        ]);

        $this->assertSame('inventory.out_of_stock', $payload['type']);
        $this->assertSame('Rice', $payload['template_data']['product_name']);
        $this->assertStringContainsString('inventory.out_of_stock', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['type'] === $payload['type'];
        });
    }

    #[Test]
    public function it_dispatches_a_subscription_expiry_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queueSubscriptionExpiryAlert([
            'business_id' => 22,
            'branch_id' => 4,
            'business_name' => 'Alpha Retail',
            'plan_name' => 'Starter',
            'expiry_date' => '2026-08-28',
            'recipient_phone' => '+256712345678',
        ]);

        $this->assertSame('subscription.expired', $payload['type']);
        $this->assertSame('Alpha Retail', $payload['template_data']['business_name']);
        $this->assertStringContainsString('subscription.expired', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['type'] === $payload['type'];
        });
    }

    #[Test]
    public function it_dispatches_a_subscription_reminder_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queueSubscriptionReminderAlert([
            'business_id' => 22,
            'branch_id' => 4,
            'business_name' => 'Alpha Retail',
            'plan_name' => 'Starter',
            'days_remaining' => 2,
            'recipient_phone' => '+256712345678',
        ]);

        $this->assertSame('subscription.expiry_reminder', $payload['type']);
        $this->assertSame(2, $payload['template_data']['days_remaining']);
        $this->assertStringContainsString('subscription.expiry_reminder', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['type'] === $payload['type'];
        });
    }

    #[Test]
    public function it_dispatches_a_free_trial_expiry_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queueFreeTrialExpiryAlert([
            'business_id' => 22,
            'branch_id' => 4,
            'business_name' => 'Alpha Retail',
            'trial_end_date' => '2026-08-30',
            'recipient_phone' => '+256712345678',
        ]);

        $this->assertSame('subscription.free_trial_expired', $payload['type']);
        $this->assertSame('Alpha Retail', $payload['template_data']['business_name']);
        $this->assertStringContainsString('subscription.free_trial_expired', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['type'] === $payload['type'];
        });
    }

    #[Test]
    public function it_dispatches_a_purchase_order_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queuePurchaseOrderAlert([
            'business_id' => 33,
            'branch_id' => 5,
            'business_name' => 'Alpha Retail',
            'order_number' => 'PO-000001',
            'supplier_name' => 'Green Foods Ltd',
            'total_amount' => 550000,
            'recipient_phone' => '+256712345678',
        ]);

        $this->assertSame('order.purchase.created', $payload['type']);
        $this->assertSame('PO-000001', $payload['template_data']['order_number']);
        $this->assertStringContainsString('order.purchase.created', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['type'] === $payload['type'];
        });
    }

    #[Test]
    public function it_dispatches_a_sale_order_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queueSaleOrderAlert([
            'business_id' => 33,
            'branch_id' => 5,
            'business_name' => 'Alpha Retail',
            'order_number' => 'ORD-000001',
            'customer_name' => 'Jane Doe',
            'total_amount' => 180000,
            'recipient_phone' => '+256712345678',
        ]);

        $this->assertSame('order.sale.created', $payload['type']);
        $this->assertSame('ORD-000001', $payload['template_data']['order_number']);
        $this->assertStringContainsString('order.sale.created', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['type'] === $payload['type'];
        });
    }

    #[Test]
    public function it_dispatches_a_monthly_business_report_notification_to_the_queue(): void
    {
        Queue::fake();

        $service = new WhatsAppNotificationService();
        $payload = $service->queueMonthlyReportAlert([
            'business_id' => 33,
            'branch_id' => 5,
            'business_name' => 'Alpha Retail',
            'month_name' => 'August',
            'total_sales' => 1240000,
            'total_purchases' => 700000,
            'profit' => 540000,
            'recipient_phone' => '+256712345678',
        ]);

        $this->assertSame('report.monthly', $payload['type']);
        $this->assertSame('August', $payload['template_data']['month_name']);
        $this->assertStringContainsString('report.monthly', $payload['dedupe_key']);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, function ($job) use ($payload) {
            return $job->payload['type'] === $payload['type'];
        });
    }
}
