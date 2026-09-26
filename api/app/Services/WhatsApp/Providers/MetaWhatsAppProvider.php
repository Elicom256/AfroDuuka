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

    /**
     * Not implemented yet — Stage 3.
     *
     * This used to return a fabricated success: an md5 of the recipient and body dressed
     * up as a `provider_message_id`, with `'status' => 'sent'`. That is the worst possible
     * shape for a not-implemented send. It reports success for a message that was never
     * transmitted, so the delivery is marked sent, the dashboard says it went out, and
     * nothing retries it. A customer simply never receives it, and the only symptom is an
     * absence nobody is looking for.
     *
     * Worse, the failure needed no bug to trigger: setting WHATSAPP_PROVIDER=meta was
     * enough. Every message would have "succeeded" silently.
     *
     * So it throws. A loud failure at the call site, where the dispatcher's error handling
     * can see it, beats a plausible lie. Implement this against
     * POST /{version}/{phone-number-id}/messages (see graphUrl) and delete this method's
     * body.
     *
     * @throws \LogicException always, until the send path is implemented
     */
    public function sendMessage(array $payload): array
    {
        throw new \LogicException(sprintf(
            'MetaWhatsAppProvider::sendMessage() is not implemented. Sending a WhatsApp '
            .'message to %s would be reported as delivered without being transmitted. '
            .'Implement the Cloud API send path (Stage 3) before setting '
            .'WHATSAPP_PROVIDER=meta, or keep WHATSAPP_PROVIDER=demo.',
            (string) ($payload['to'] ?? '(no recipient)'),
        ));
    }

    /**
     * The Graph base URL for this app, on the one configured API version.
     *
     * Every call goes through here so that listing and sending cannot drift onto
     * different versions. Meta expires versions on a schedule — v21.0, which this file
     * used to hardcode, stops being served on 2027-01-21 — and a provider that mixes
     * versions is the situation where a half of the integration breaks on a date nobody
     * put in a calendar.
     */
    private function graphUrl(string $path): string
    {
        $version = trim((string) config('services.whatsapp.graph_api_version', 'v25.0'));

        return sprintf('https://graph.facebook.com/%s/%s', $version, ltrim($path, '/'));
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
                ->get($this->graphUrl($wabaId).'/message_templates', [
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
