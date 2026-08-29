<?php

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use App\Services\WhatsApp\Providers\DemoWhatsAppProvider;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhaseOneArchitectureTest extends TestCase
{
    #[Test]
    public function it_has_a_demo_provider_that_implements_the_provider_contract(): void
    {
        $provider = new DemoWhatsAppProvider();

        $this->assertInstanceOf(WhatsAppProviderInterface::class, $provider);
        $this->assertSame('demo', $provider->getName());
    }

    #[Test]
    public function it_builds_a_demo_provider_from_the_factory(): void
    {
        $provider = WhatsAppProviderFactory::create([
            'provider' => 'demo',
            'business_phone' => '+256731794401',
            'access_token' => 'demo_access_token',
        ]);

        $this->assertInstanceOf(DemoWhatsAppProvider::class, $provider);
        $this->assertSame('demo', $provider->getName());
    }

    #[Test]
    public function it_can_send_a_demo_message_and_return_success_payload(): void
    {
        $provider = new DemoWhatsAppProvider();

        $result = $provider->sendMessage([
            'to' => '+256712345678',
            'message' => 'Welcome to DuukaFlow',
            'template_key' => 'welcome',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('demo', $result['provider']);
        $this->assertSame('Welcome to DuukaFlow', $result['message']);
    }
}
