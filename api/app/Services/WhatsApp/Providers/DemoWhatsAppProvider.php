<?php

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;

class DemoWhatsAppProvider implements WhatsAppProviderInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'demo';
    }

    public function validateConfiguration(array $config): bool
    {
        return ! empty($config['business_phone']) || ! empty($config['access_token']) || ! empty($config['phone_number_id']);
    }

    /**
     * The demo provider has no remote template registry, and demo sends bypass the
     * approval gate entirely, so there is nothing to sync.
     */
    public function listTemplates(): array
    {
        return [];
    }

    public function sendMessage(array $payload): array
    {
        $message = (string) ($payload['message'] ?? 'Demo WhatsApp message');
        $recipient = (string) ($payload['to'] ?? $payload['recipient'] ?? 'demo-recipient');

        return [
            'success' => true,
            'provider' => $this->getName(),
            'message' => $message,
            'recipient' => $recipient,
            'mode' => 'demo',
            'provider_message_id' => 'demo-'.md5($recipient.':'.$message.':'.now()->timestamp),
            'status' => 'sent',
            'warning' => 'Demo delivery only. No paid WhatsApp API configured yet.',
        ];
    }
}
