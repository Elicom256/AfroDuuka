<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use App\Services\WhatsApp\Providers\DemoWhatsAppProvider;
use App\Services\WhatsApp\Providers\MetaWhatsAppProvider;

class WhatsAppProviderFactory
{
    public static function create(array $config = []): WhatsAppProviderInterface
    {
        $providerName = strtolower((string) ($config['provider'] ?? config('services.whatsapp.provider', 'demo')));

        return match ($providerName) {
            'demo' => new DemoWhatsAppProvider($config),
            'meta' => new MetaWhatsAppProvider($config),
            default => new DemoWhatsAppProvider($config),
        };
    }
}
