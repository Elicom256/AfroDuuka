<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppTemplateService;
use App\Models\WhatsAppConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendFreePeriodExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $notificationService = new WhatsAppNotificationService();
        $templateService = new WhatsAppTemplateService();

        $config = WhatsAppConfig::where('provider', 'demo')
            ->orWhere('is_active', true)
            ->first();

        if (! $config) {
            Log::info('No active WhatsApp config found for free period expiry job');
            return;
        }

        $provider = WhatsAppProviderFactory::create([
            'provider' => $config->provider,
            'business_phone' => $config->business_phone,
            'access_token' => $config->access_token,
            'phone_number_id' => $config->phone_number_id,
        ]);

        // Businesses whose free trial period has ended
        $businesses = \App\Models\Business::whereNotNull('free_ends_at')
            ->where('free_ends_at', '<', now())
            ->whereDoesntHave('whatsappMessageLogs', function ($query) {
                $query->where('status', 'sent')
                    ->where('type', 'subscription.free_trial_expired');
            })
            ->get();

        foreach ($businesses as $business) {
            $dedupeKey = 'notification:free_trial:expired:business-' . $business->id;

            $existingLog = WhatsAppMessageLog::where('business_id', $business->id)
                ->where('dedupe_key', $dedupeKey)
                ->where('status', 'sent')
                ->first();

            if ($existingLog) {
                Log::info('Skipping duplicate free trial expiry notification', [
                    'business_id' => $business->id,
                    'dedupe_key' => $dedupeKey,
                ]);
                continue;
            }

            $payload = [
                'business_id' => $business->id,
                'branch_id' => null,
                'type' => 'subscription.free_trial_expired',
                'template_key' => 'subscription.free_trial_expired',
                'recipient_phone' => $business->phone ?? '+256731794401',
                'template_data' => [
                    'business_name' => $business->name,
                    'trial_end_date' => $business->free_ends_at?->format('Y-m-d') ?? now()->subMonth()->format('Y-m-d'),
                ],
            ];

            $normalized = $notificationService->buildPayload($payload);

            $provider->sendMessage([
                'to' => $normalized['recipient_phone'],
                'message' => (string) ($templateService->render(
                    $config->message_template ?? 'Hello {{business_name}}, your free trial ended on {{trial_end_date}}. Subscribe to continue accessing all features.',
                    $normalized['template_data'] ?? []
                )),
                'template_key' => $normalized['template_key'],
                'business_id' => $business->id,
            ]);

            WhatsAppMessageLog::create([
                'business_id' => $business->id,
                'template_id' => null,
                'recipient' => $normalized['recipient_phone'],
                'channel' => 'whatsapp',
                'message_body' => $normalized['template_data'] ?? [],
                'status' => 'sent',
                'provider_response' => ['provider' => $provider->getName()],
                'sent_at' => now(),
                'dedupe_key' => $normalized['dedupe_key'],
            ]);

            Log::info('Free trial expiry notification sent', [
                'business_id' => $business->id,
                'dedupe_key' => $normalized['dedupe_key'],
            ]);
        }
    }
}