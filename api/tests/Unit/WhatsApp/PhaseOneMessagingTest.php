<?php

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppTemplateService;
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
}
