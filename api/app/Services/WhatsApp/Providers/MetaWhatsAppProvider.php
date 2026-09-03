<?php

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;

class MetaWhatsAppProvider implements WhatsAppProviderInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'meta';
    }

    public function validateConfiguration(array $config): bool
    {
        return ! empty($config['business_phone']) || ! empty($config['access_token']) || ! empty($config['phone_number_id']);
    }

    public function sendMessage(array $payload): array
    {
        $to = (string) ($payload['to'] ?? '');
        $message = (string) ($payload['message'] ?? '');
        $templateKey = (string) ($payload['template_key'] ?? 'general');

        // In a real implementation, this would call the Meta WhatsApp Business API
        // For now, return a structured response that can be used for logging

        return [
            'success' => ! empty($to) && ! empty($message),
            'provider' => $this->getName(),
            'message' => $message,
            'recipient' => $to,
            'template_key' => $templateKey,
            'mode' => 'meta_api',
            'provider_message_id' => 'meta-' . md5($to . ':' . $message . ':' . now()->timestamp),
            'status' => 'sent',
            'warning' => 'Meta WhatsApp API integration - requires valid access_token and phone_number_id configuration.',
        ];
    }
}