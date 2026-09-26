<?php

namespace App\Services\WhatsApp\Providers;

use App\Services\Notifications\AddressNormaliser;
use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
     * POST /{version}/{phone-number-id}/messages
     *
     * The real send. The substance of this method is not the HTTP call — it is deciding,
     * for every way the call can end, whether Meta took the message. That decision is what
     * the channel turns into a delivery state, and the two ways of getting it wrong fail in
     * opposite directions:
     *
     *   - Call something a rejection that was really a timeout, and the send is retried.
     *     If the first attempt did land, the customer gets the message twice.
     *   - Call something a timeout that was really a rejection, and the row sits in
     *     `sending` forever waiting for a status webhook that will never describe a
     *     message Meta never accepted.
     *
     * So the rule is: only an outcome where we genuinely cannot know becomes ambiguous,
     * and that is a connection that died without an answer, or a 5xx from Meta, which
     * may have failed before or after accepting. Everything Meta actively answered is
     * definitive, including the one that looks transient — see the 429 branch.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendMessage(array $payload): array
    {
        $phoneNumberId = trim((string) ($this->config['phone_number_id'] ?? ''));
        $token = trim((string) ($this->config['access_token'] ?? ''));

        // Checked here, at the boundary, rather than trusted from the caller.
        // validateConfiguration() only requires one of three fields, so a row with just
        // a business phone passes validation and then cannot address a send at all.
        // Nothing was transmitted, so this is a rejection and not an ambiguous send.
        if ($phoneNumberId === '' || $token === '') {
            return [
                'error' => 'This WhatsApp config is missing a phone number ID or an access token.',
                'code' => 'not_configured',
            ];
        }

        // Normalised here, not by the caller. A recipient that is not E.164 cannot be
        // sent, and quietly passing it through would hand a customer's number to Meta in
        // a shape Meta rejects with a 400 we then have to interpret.
        $to = app(AddressNormaliser::class)->phone($payload['to'] ?? null);

        if ($to === null) {
            return [
                'error' => 'Recipient is not a usable phone number in E.164 form.',
                'code' => 'invalid_recipient',
            ];
        }

        $template = is_array($payload['template'] ?? null) ? $payload['template'] : [];
        $name = trim((string) ($template['name'] ?? ''));

        if ($name === '') {
            return [
                'error' => 'No approved template name was resolved for this delivery.',
                'code' => 'no_template',
            ];
        }

        $parameters = array_values(array_filter(
            array_map(
                static fn ($parameter): array => ['type' => 'text', 'text' => (string) $parameter],
                is_array($template['parameters'] ?? null) ? $template['parameters'] : [],
            ),
            static fn (array $parameter): bool => $parameter['text'] !== '',
        ));

        $body = $this->messageBody($to, $name, $parameters);

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->post($this->graphUrl($phoneNumberId).'/messages', $body);
        } catch (ConnectionException $e) {
            // No answer at all. The request may have reached Meta and been accepted
            // before the connection died, so this is the ambiguous case: leave the row
            // in `sending` and let the status webhook decide rather than risk a repeat.
            return [
                'error' => $this->message('Could not reach Meta: '.$e->getMessage()),
                'code' => 'connection_error',
            ];
        }

        if ($response->successful()) {
            $body = $response->json();

            // A 2xx with no readable message id in it. Meta's send endpoint always returns
            // `messages[0].id`, so this means the response is not shaped like a send
            // response — a proxy, a captive portal, a future change.
            //
            // Returning the body as-is would be read downstream as a successful send with a
            // null id, and a null id is invisible: the dashboard says delivered, and the
            // status webhook can never match the row because it has nothing to match on.
            // That is the same fabricated-success shape this class used to return, reached
            // by a different road. Reported as unreadable instead, so the row stays in
            // `sending` and is visibly unresolved.
            if (! is_array($body) || ! isset($body['messages'][0]['id'])) {
                return [
                    'error' => sprintf(
                        'Meta returned HTTP %d with no readable message id.',
                        $response->status(),
                    ),
                    'code' => 'unreadable_response',
                ];
            }

            // Meta answers a send with `messages[0].id`. Returned verbatim so the channel
            // reads the same shape it reads from any other provider.
            return $body;
        }

        return $this->failure($response);
    }

    /**
     * The Cloud API's template message body.
     *
     * `type` is the literal `template`, not the notification's template key. The channel
     * passes our own key in `payload['type']` (e.g. `registration.welcome`) for
     * bookkeeping, and sending that where Meta wants a message type would be rejected by
     * every request.
     *
     * @param  array<int, array{type: string, text: string}>  $parameters
     * @return array<string, mixed>
     */
    private function messageBody(string $to, string $templateName, array $parameters): array
    {
        $body = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en',
                ],
            ],
        ];

        // Omitted entirely when empty. Meta rejects an empty `components` array against a
        // template that has no placeholders, so sending one turns a valid send into a 400.
        if ($parameters !== []) {
            $body['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => $parameters,
                ],
            ];
        }

        return $body;
    }

    /**
     * Classify a response Meta actually answered with.
     *
     * @return array<string, mixed>
     */
    private function failure(Response $response): array
    {
        $error = $response->json('error');
        $message = is_array($error)
            ? (string) ($error['message'] ?? json_encode($error))
            : (string) ($response->body() ?: 'Meta rejected the message.');

        $detail = is_array($error) && isset($error['error_data']['details'])
            ? (string) $error['error_data']['details']
            : null;

        $message = $this->message($detail === null ? $message : $message.' '.$detail);

        if ($response->status() === 429) {
            // Deliberately a rejection and not an ambiguous send. Meta answered, so
            // nothing was sent and a status webhook will never mention this message.
            // Filing it as ambiguous would strand it in `sending` with no way out.
            //
            // It is also not an ordinary rejection: this is a rate limit, it clears, and
            // the message should go out later. The code is kept distinct so the throttle
            // rails can attach real backoff to it instead of treating it as a bad
            // payload that will never succeed.
            return ['error' => $message, 'code' => 'throttled', 'retry_after' => $response->header('Retry-After')];
        }

        if ($response->status() >= 500) {
            // A server fault is the one answered failure that is still not definitive:
            // Meta may have failed after accepting the message. Treated as ambiguous, so
            // the worst case is a delivery recorded as unresolved rather than a duplicate.
            return ['error' => $message, 'code' => 'timeout'];
        }

        if (in_array($response->status(), [401, 403], true)) {
            return ['error' => $message, 'code' => 'auth'];
        }

        // 4xx: Meta parsed the request and refused it. The Meta code is preserved
        // because it is the actionable part — 132012 for instance means the parameter
        // count no longer matches the approved template.
        $metaCode = is_array($error) && isset($error['code'])
            ? (string) $error['code']
            : (string) $response->status();

        return ['error' => $message, 'code' => $metaCode];
    }

    /**
     * Trimmed and capped so one verbose Graph error cannot bloat the delivery row; the
     * job does the same for exceptions, and the two should read alike.
     */
    private function message(string $message): string
    {
        $message = trim($message);

        return Str::limit($message !== '' ? $message : 'Meta rejected the message.', 1000);
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
