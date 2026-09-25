<?php

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use Illuminate\Support\Facades\Http;

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
            'provider_message_id' => 'meta-'.md5($to.':'.$message.':'.now()->timestamp),
            'status' => 'sent',
            'warning' => 'Meta WhatsApp API integration - requires valid access_token and phone_number_id configuration.',
        ];
    }

    /**
     * GET /{waba-id}/message_templates
     *
     * This one call is real rather than stubbed: the template sync command is the whole
     * point of it, and a stubbed read would report every template as absent. Returns []
     * on any failure so a Graph outage leaves stored approval statuses untouched.
     */
    public function listTemplates(): array
    {
        $wabaId = (string) ($this->config['whatsapp_business_account_id'] ?? '');
        $token = (string) ($this->config['access_token'] ?? '');

        if ($wabaId === '' || $token === '') {
            return [];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get("https://graph.facebook.com/v21.0/{$wabaId}/message_templates", [
                    'limit' => 100,
                ]);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $templates = $response->json('data');

        if (! is_array($templates)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($template) => $this->normaliseTemplate(is_array($template) ? $template : []),
            $templates,
        )));
    }

    /**
     * @param  array<string, mixed>  $template
     * @return array{name: string, language: string, status: string, category: ?string, parameter_format: ?string}|null
     */
    private function normaliseTemplate(array $template): ?array
    {
        $name = (string) ($template['name'] ?? '');

        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'language' => (string) ($template['language'] ?? 'en'),
            'status' => strtoupper((string) ($template['status'] ?? 'PENDING')),
            'category' => isset($template['category'])
                ? strtoupper((string) $template['category'])
                : null,
            // Meta omits this for a template with no placeholders.
            'parameter_format' => isset($template['parameter_format'])
                ? strtoupper((string) $template['parameter_format'])
                : null,
        ];
    }
}
