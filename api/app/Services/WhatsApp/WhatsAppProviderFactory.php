<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use App\Services\WhatsApp\Providers\DemoWhatsAppProvider;
use App\Services\WhatsApp\Providers\MetaWhatsAppProvider;

class WhatsAppProviderFactory
{
    /**
     * Resolve the provider for a stored config.
     *
     * Secrets are read off the model, which decrypts them through its cast, so callers
     * never handle the raw value. Falls back to demo for an unknown provider name
     * rather than throwing: a typo in the provider column should degrade to a no-op
     * send that is logged, not take down whatever business event triggered it.
     */
    public function for(WhatsAppConfig $config): WhatsAppProviderInterface
    {
        return static::create([
            'provider' => $config->provider,
            'business_phone' => $config->business_phone,
            'phone_number_id' => $config->phone_number_id,
            'access_token' => $config->access_token,
        ]);
    }

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
