<?php

namespace App\Jobs;

use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessageLog;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\ProviderResponse;
use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300];
    }

    public function __construct(
        public array $payload
    ) {
    }

    public function handle(): void
    {
        $notificationService = new WhatsAppNotificationService();
        $templateService = new WhatsAppTemplateService();

        $normalized = $notificationService->buildPayload($this->payload);
        $businessId = (int) ($normalized['business_id'] ?? 0);
        $dedupeKey = $normalized['dedupe_key'] ?? null;
        $templateKey = $normalized['template_key'] ?? 'general';

        // Resolved first and refused if absent, before any row is read or written.
        //
        // It used to fall back to `$config->business_phone` at the point of sending,
        // which quietly made the sending identity double as the destination: a send with
        // no recipient became a message to the business's own number, recorded against a
        // recipient nobody chose. A missing recipient is missing, not an instruction to
        // self-address. Both callers dispatch an explicit recipient, so this is a
        // backstop rather than a live path.
        $recipient = trim((string) ($normalized['recipient_phone'] ?? ''));

        if ($recipient === '') {
            Log::warning('WhatsApp notification skipped: payload carried no recipient', [
                'business_id' => $businessId,
                'template_key' => $templateKey,
            ]);

            return;
        }

        if ($dedupeKey) {
            $existingLog = WhatsAppMessageLog::where('business_id', $businessId)
                ->where('dedupe_key', $dedupeKey)
                ->where('status', 'sent')
                ->first();

            if ($existingLog) {
                Log::info('Skipping duplicate WhatsApp notification', [
                    'business_id' => $businessId,
                    'dedupe_key' => $dedupeKey,
                    'existing_log_id' => $existingLog->id,
                ]);
                return;
            }
        }

        $config = WhatsAppConfig::where('business_id', $businessId)->first();

        if (! $config) {
            $config = WhatsAppConfig::create([
                'business_id' => $businessId,
                'provider' => config('services.whatsapp.provider', 'demo'),
                // No default. This used to be `config(..., '+256731794401')`, so every
                // business that reached a send without a config row of its own was given
                // a sending identity belonging to one specific handset. There is no
                // correct value to invent here: an unconfigured business has no sending
                // identity, and the demo provider does not need one to send.
                'business_phone' => config('services.whatsapp.business_phone'),
                'phone_number_id' => config('services.whatsapp.phone_number_id', 'demo_phone_number_id'),
                'access_token' => config('services.whatsapp.access_token', 'demo_access_token'),
                'webhook_verify_token' => config('services.whatsapp.webhook_verify_token', 'demo_verify_token'),
                'is_active' => true,
                'message_template' => config('services.whatsapp.default_template', 'demo_business_alert'),
                'welcome_message' => 'Welcome to DuukaFlow WhatsApp demo mode.',
            ]);
        }

        // Resolved once, and refused if absent. Both callers dispatch an explicit
        // recipient, and the service refuses to dispatch without one, so reaching this
        // with nothing means something upstream changed shape.
        //
        // It used to fall back to `$config->business_phone` here, which quietly made the
        // sending identity double as the destination: a send with no recipient became a
        // message to the business's own number, recorded against a recipient nobody
        // chose. A missing recipient is missing, not an instruction to self-address.
        $recipient = trim((string) ($normalized['recipient_phone'] ?? ''));

        if ($recipient === '') {
            Log::warning('WhatsApp notification skipped: payload carried no recipient', [
                'business_id' => $businessId,
                'template_key' => $templateKey,
            ]);

            return;
        }

        $provider = WhatsAppProviderFactory::create([
            'provider' => $config->provider,
            'business_phone' => $config->business_phone,
            'access_token' => $config->access_token,
            'phone_number_id' => $config->phone_number_id,
        ]);

        // Match the full template_key against the template name. Matching on a
        // suffix or on `category` mis-routes: subscription.created,
        // order.purchase.created and order.sale.created all end in "created".
        $template = WhatsAppTemplate::where('business_id', $businessId)
            ->where('name', $templateKey)
            ->where('status', 'approved')
            ->latest('created_at')
            ->first();

        // A template's `variables` column holds the variable *names* it expects,
        // not values, so it must never be used as the render data.
        $templateString = $template?->body ?? ($config->message_template ?? 'Hello {{business_name}}, this is a DuukaFlow WhatsApp alert.');
        $templateData = $normalized['template_data'] ?? [];
        $message = $templateService->render($templateString, $templateData);

        $result = $provider->sendMessage([
            'to' => $recipient,
            'message' => $message,
            'template_key' => $templateKey,
            'business_id' => $businessId,
        ]);

        $outcome = ProviderResponse::interpret($result);

        // This table is a deprecated read-only mirror of the WhatsApp subset of
        // notification_deliveries, so it has no third state for "we do not know" and
        // both non-accepted outcomes land on 'failed'. Recording them apart would be
        // inventing a status nothing reads; the reason is kept in error_code either way,
        // which is more than the blanket 'provider_error' this used to write for every
        // refusal regardless of cause.
        //
        // Not `$result['success']`, which the demo provider always sets and Meta never
        // sets: that raised an undefined-key warning on every genuine failure and fell
        // through to 'failed' by accident.
        $log = WhatsAppMessageLog::create([
            'business_id' => $businessId,
            'template_id' => $template?->id,
            'recipient' => $recipient,
            'channel' => 'whatsapp',
            'message_body' => $message,
            'variables' => $templateData,
            'status' => $outcome->isSuccessful() ? 'sent' : 'failed',
            'provider_response' => $result,
            'error_code' => $outcome->isSuccessful() ? null : ($outcome->errorCode ?? 'provider_error'),
            'sent_at' => $outcome->isSuccessful() ? now() : null,
            'dedupe_key' => $normalized['dedupe_key'] ?? null,
        ]);

        Log::info('WhatsApp notification processed', [
            'business_id' => $businessId,
            'recipient' => $log->recipient,
            'status' => $log->status,
            'error_code' => $log->error_code,
            'provider' => $result['provider'] ?? 'demo',
            'dedupe_key' => $normalized['dedupe_key'] ?? null,
        ]);
    }
}
