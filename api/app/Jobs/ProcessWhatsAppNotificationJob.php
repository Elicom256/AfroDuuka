<?php

namespace App\Jobs;

use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessageLog;
use App\Models\WhatsAppTemplate;
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
                'business_phone' => config('services.whatsapp.business_phone', '+256731794401'),
                'phone_number_id' => config('services.whatsapp.phone_number_id', 'demo_phone_number_id'),
                'access_token' => config('services.whatsapp.access_token', 'demo_access_token'),
                'webhook_verify_token' => config('services.whatsapp.webhook_verify_token', 'demo_verify_token'),
                'is_active' => true,
                'message_template' => config('services.whatsapp.default_template', 'demo_business_alert'),
                'welcome_message' => 'Welcome to DuukaFlow WhatsApp demo mode.',
            ]);
        }

        $provider = WhatsAppProviderFactory::create([
            'provider' => $config->provider,
            'business_phone' => $config->business_phone,
            'access_token' => $config->access_token,
            'phone_number_id' => $config->phone_number_id,
        ]);

        $templateKey = $normalized['template_key'] ?? 'general';
        $template = WhatsAppTemplate::where('business_id', $businessId)
            ->where('category', $templateKey)
            ->where('status', 'approved')
            ->latest('created_at')
            ->first();

        $templateString = $template?->body ?? ($config->message_template ?? 'Hello {{business_name}}, this is a DuukaFlow WhatsApp alert.');
        $templateData = $template?->variables ?? ($normalized['template_data'] ?? []);
        $message = $templateService->render($templateString, $templateData);

        $result = $provider->sendMessage([
            'to' => $normalized['recipient_phone'] ?: $config->business_phone,
            'message' => $message,
            'template_key' => $templateKey,
            'business_id' => $businessId,
        ]);

        $log = WhatsAppMessageLog::create([
            'business_id' => $businessId,
            'template_id' => $template?->id,
            'recipient' => $normalized['recipient_phone'] ?: $config->business_phone,
            'channel' => 'whatsapp',
            'message_body' => $message,
            'variables' => $templateData,
            'status' => $result['success'] ? 'sent' : 'failed',
            'provider_response' => $result,
            'error_code' => $result['success'] ? null : 'provider_error',
            'sent_at' => $result['success'] ? now() : null,
            'dedupe_key' => $normalized['dedupe_key'] ?? null,
        ]);

        Log::info('WhatsApp notification processed', [
            'business_id' => $businessId,
            'recipient' => $log->recipient,
            'status' => $log->status,
            'provider' => $result['provider'] ?? 'demo',
            'dedupe_key' => $normalized['dedupe_key'] ?? null,
        ]);
    }
}
