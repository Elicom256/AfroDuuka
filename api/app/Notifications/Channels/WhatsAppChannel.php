<?php

namespace App\Notifications\Channels;

use App\Contracts\Notifications\ChannelResult;
use App\Contracts\Notifications\NotificationChannel;
use App\Models\NotificationDelivery;
use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppService;

/**
 * Sends over WhatsApp via whichever provider the business configured.
 *
 * The template is not re-resolved here. The delivery row already carries
 * meta_template_name and meta_template_language, and re-resolving at send time would
 * mean a template edited between dispatch and send silently changes what goes out.
 */
class WhatsAppChannel implements NotificationChannel
{
    public function __construct(
        private readonly WhatsAppProviderFactory $providers,
        private readonly WhatsAppService $service,
    ) {}

    public function name(): string
    {
        return 'whatsapp';
    }

    public function isAvailable(NotificationDelivery $delivery): bool
    {
        return $this->config($delivery)?->is_active === true;
    }

    public function unavailableReason(NotificationDelivery $delivery): ?string
    {
        $config = $this->config($delivery);

        if ($config === null || ! $config->is_active) {
            return NotificationDelivery::REASON_NO_RECIPIENT;
        }

        return null;
    }

    public function send(NotificationDelivery $delivery, array $parameters): ChannelResult
    {
        $config = $this->config($delivery);

        if ($config === null) {
            return ChannelResult::rejected('no_config', 'No WhatsApp config for this business.');
        }

        $address = $delivery->recipient_address;

        if ($address === null) {
            return ChannelResult::rejected('no_address', 'Delivery has no recipient address.');
        }

        $provider = $this->providers->for($config);

        if (! $provider instanceof WhatsAppProviderInterface) {
            return ChannelResult::rejected('no_provider', 'No WhatsApp provider resolved.');
        }

        $response = $provider->sendMessage([
            'to' => $address,
            'type' => $delivery->template_key,
            'template' => [
                'name' => $delivery->meta_template_name,
                'language' => $delivery->meta_template_language,
                'parameters' => array_values($parameters),
            ],
            'business_id' => $config->business_id,
        ]);

        $delivery->forceFill(['provider' => $provider->getName()])->save();

        // The provider layer returns a plain array; interpreting it is kept here so the
        // demo and real providers can differ in shape without the job caring.
        return $this->interpret($response);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function interpret(array $response): ChannelResult
    {
        $error = $response['error'] ?? null;

        if ($error !== null) {
            $code = (string) ($response['code'] ?? 'provider_error');
            $message = is_array($error) ? json_encode($error) : (string) $error;

            // Codes where the request may or may not have landed. A timeout tells us
            // nothing about whether it was delivered, so it is ambiguous rather than a
            // failure: retrying it is how a customer gets it twice. `unreadable_response`
            // belongs here for the same reason — Meta answered 2xx, so it probably took
            // the message, but the reply could not be parsed and there is no id to
            // reconcile on. Calling it a failure would invite a resend of a message that
            // probably went out.
            if (in_array($code, ['timeout', 'curl_timeout', 'connection_error', 'unreadable_response'], true)) {
                // The provider's own code is carried through, so the row records which
                // kind of doubt this was rather than a flat "ambiguous" for all of them.
                return ChannelResult::ambiguous($message, $response, $code);
            }

            return ChannelResult::rejected($code, $message, $response);
        }

        // Three shapes, because the providers disagree and the disagreement was silent.
        // Meta returns `messages[0].id`; the demo provider returns `provider_message_id`.
        // Reading only the first two accepted every send with a null message id, so the
        // delivery was marked sent with nothing to correlate against — and the status
        // webhook, whose whole job is to match a delivery to Meta's message id, would
        // have matched nothing at all. No test noticed, because nothing asserted the id
        // was stored.
        $messageId = $response['message_id']
            ?? $response['messages'][0]['id']
            ?? $response['provider_message_id']
            ?? null;

        return ChannelResult::accepted($messageId === null ? null : (string) $messageId, $response);
    }

    /**
     * Resolve through WhatsAppService so a business with no config row still gets the
     * demo sender, which is what that service has always done.
     *
     * Treating a missing config as "cannot send" was wrong: it made "never configured
     * WhatsApp" indistinguishable from "opted out", and since the recipient address
     * falls back to the business phone, the send was legitimate in every case except a
     * deliberately deactivated config.
     */
    private function config(NotificationDelivery $delivery): ?WhatsAppConfig
    {
        return $this->service->getConfigForBusiness($delivery->business_id);
    }
}
