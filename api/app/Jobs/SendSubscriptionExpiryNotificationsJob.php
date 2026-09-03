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

class SendSubscriptionExpiryNotificationsJob implements ShouldQueue
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
            Log::info('No active WhatsApp config found for subscription expiry job');
            return;
        }

        $provider = WhatsAppProviderFactory::create([
            'provider' => $config->provider,
            'business_phone' => $config->business_phone,
            'access_token' => $config->access_token,
            'phone_number_id' => $config->phone_number_id,
        ]);

        // Businesses with subscriptions ending within 2 days
        // This is a simplified query - in production would join with subscriptions table
        $businesses = \App\Models\Business::whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<=', now()->addDays(2))
            ->get();

        foreach ($businesses as $business) {
            $dedupeKey = 'notification:subscription:expiry:business-' . $business->id;

            $existingLog = WhatsAppMessageLog::where('business_id', $business->id)
                ->where('dedupe_key', $dedupeKey)
                ->where('status', 'sent')
                ->first();

            if ($existingLog) {
                Log::info('Skipping duplicate subscription expiry notification', [
                    'business_id' => $business->id,
                    'dedupe_key' => $dedupeKey,
                ]);
                continue;
            }

            $payload = [
                'business_id' => $business->id,
                'branch_id' => null,
                'type' => 'subscription.expired',
                'template_key' => 'subscription.expired',
                'recipient_phone' => $business->phone ?? '+256731794401',
                'template_data' => [
                    'business_name' => $business->name,
                    'plan_name' => $business->current_plan?->name ?? 'Your plan',
                    'expiry_date' => $business->subscription_ends_at?->format('Y-m-d') ?? now()->addMonth()->format('Y-m-d'),
                ],
            ];

            $normalized = $notificationService->buildPayload($payload);

            $provider->sendMessage([
                'to' => $normalized['recipient_phone'],
                'message' => (string) ($templateService->render(
                    $config->message_template ?? 'Hello {{business_name}}, your subscription expires on {{expiry_date}}.',
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

            Log::info('Subscription expiry notification sent', [
                'business_id' => $business->id,
                'dedupe_key' => $normalized['dedupe_key'],
            ]);
        }
    }
}