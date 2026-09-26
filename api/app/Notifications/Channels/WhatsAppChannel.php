<?php

namespace App\Notifications\Channels;

use App\Contracts\Notifications\ChannelResult;
use App\Contracts\Notifications\NotificationChannel;
use App\Models\NotificationDelivery;
use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use App\Services\WhatsApp\ProviderResponse;
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

        // The provider layer returns a plain array and the implementations disagree
        // about its shape, so reading it is ProviderResponse's one job.
        return ProviderResponse::interpret($response);
    }

    /**
     * Resolve through WhatsAppService so a business with no config row still gets the
     * demo sender, which is what that service has always done.
     *
     * Treating a missing config as "cannot send" was wrong: it made "never configured
     * WhatsApp" indistinguishable from "opted out", and the two need different answers
     * from this method. The first is a business that has not got round to it, and
     * quietly doing nothing for it is the right answer.
     */
    private function config(NotificationDelivery $delivery): ?WhatsAppConfig
    {
        return $this->service->getConfigForBusiness($delivery->business_id);
    }
}
