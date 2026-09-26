<?php

namespace App\Services\WhatsApp;

use App\Jobs\ProcessWhatsAppNotificationJob;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessageLog;
use App\Models\WhatsAppTemplate;
use App\Services\Notifications\TemplateProvisioner;
use Illuminate\Support\Facades\Auth;

class WhatsAppService
{
    /**
     * Resolve or create the WhatsApp config for the current business.
     * This is intentionally demo-safe until a real paid provider is configured.
     */
    public function getConfigForBusiness(?int $businessId = null): WhatsAppConfig
    {
        $businessId = $businessId ?? Auth::user()?->business_id;

        $config = WhatsAppConfig::where('business_id', $businessId)->first();

        if ($config) {
            return $config;
        }

        return WhatsAppConfig::create([
            'business_id' => $businessId,
            'provider' => 'demo',
            // No invented sending identity. This used to be a literal phone number, so
            // every business that had never configured WhatsApp was recorded as sending
            // from a specific handset that had nothing to do with it. Read from config
            // now, so a deployment can set one deliberately and the default is nothing.
            'business_phone' => config('services.whatsapp.business_phone'),
            'phone_number_id' => 'demo_phone_number_id',
            'access_token' => 'demo_access_token',
            'webhook_verify_token' => 'demo_verify_token',
            'is_active' => true,
            'message_template' => 'demo_business_alert',
            'welcome_message' => 'Welcome to DuukaFlow WhatsApp demo mode.',
        ]);
    }

    /**
     * Give the business its catalogue template rows, creating any that are missing.
     *
     * The old implementation seeded two hand-written rows, low_stock_alert and
     * payment_reminder, under the pre-catalogue naming with no Meta template identity.
     * Nothing dispatched against them and nothing resolved them, because the dispatcher
     * matches on the notification type. Wording now comes from
     * App\Services\Notifications\TemplateProvisioner, which derives the mapping from
     * config('notifications.catalogue') so the two cannot drift.
     */
    public function ensureTemplatesForBusiness(?int $businessId = null): array
    {
        $businessId ??= Auth::user()?->business_id;

        if ($businessId === null) {
            return [];
        }

        app(TemplateProvisioner::class)->ensureForBusiness($businessId);

        return WhatsAppTemplate::where('business_id', $businessId)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Queue a WhatsApp message so outbound delivery is handled asynchronously.
     */
    public function queueDemoMessage(string $recipient, string $message, ?WhatsAppConfig $config = null): array
    {
        $config ??= $this->getConfigForBusiness();

        $notification = (new WhatsAppNotificationService)->buildPayload([
            'business_id' => $config->business_id,
            'branch_id' => null,
            'type' => 'demo_test',
            'template_key' => 'demo_test',
            'recipient_phone' => $recipient,
            'template_data' => [
                'business_name' => 'DuukaFlow',
                'message' => $message,
            ],
        ]);

        ProcessWhatsAppNotificationJob::dispatch($notification);

        return [
            'success' => true,
            'business_number' => $config->business_phone,
            'provider' => $config->provider,
            'message' => 'Demo WhatsApp message queued for processing.',
            'recipient' => $recipient,
            'queued' => true,
            'dedupe_key' => $notification['dedupe_key'],
        ];
    }

    public function sendDemoMessage(string $recipient, string $message, ?WhatsAppConfig $config = null): array
    {
        $config ??= $this->getConfigForBusiness();

        $payload = [
            'business_id' => $config->business_id,
            'branch_id' => null,
            'type' => 'demo_test',
            'template_key' => 'demo_test',
            'recipient_phone' => $recipient,
            'template_data' => [
                'business_name' => 'DuukaFlow',
                'message' => $message,
            ],
        ];

        $notification = (new WhatsAppNotificationService)->buildPayload($payload);
        $provider = WhatsAppProviderFactory::create([
            'provider' => $config->provider,
            'business_phone' => $config->business_phone,
            'access_token' => $config->access_token,
            'phone_number_id' => $config->phone_number_id,
        ]);

        $result = $provider->sendMessage([
            'to' => $recipient,
            'message' => $message,
            'template_key' => 'demo_test',
            'business_id' => $config->business_id,
        ]);

        $outcome = ProviderResponse::interpret($result);

        $log = WhatsAppMessageLog::create([
            'business_id' => $config->business_id,
            'template_id' => null,
            'recipient' => $recipient,
            'channel' => 'whatsapp',
            'message_body' => $message,
            'variables' => ['business_phone' => $config->business_phone],
            // Not `$result['success']`, which Meta never sets. See ProviderResponse.
            'status' => $outcome->isSuccessful() ? 'sent' : 'failed',
            'provider_response' => $result,
            'error_code' => $outcome->isSuccessful() ? null : ($outcome->errorCode ?? 'provider_error'),
            'sent_at' => $outcome->isSuccessful() ? now() : null,
        ]);

        return [
            'success' => $outcome->isSuccessful(),
            'business_number' => $config->business_phone,
            'provider' => $config->provider,
            'message' => $outcome->isSuccessful()
                ? 'Demo WhatsApp message queued successfully. No live API billing is attached yet.'
                : 'The provider refused this message: '.($outcome->errorMessage ?? 'no reason given'),
            'recipient' => $recipient,
            'log_id' => $log->id,
            'dedupe_key' => $notification['dedupe_key'],
        ];
    }
}
