<?php

namespace App\Services\WhatsApp;

use App\Jobs\ProcessWhatsAppNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessageLog;
use App\Models\WhatsAppTemplate;
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
            'business_phone' => '+256731794401',
            'phone_number_id' => 'demo_phone_number_id',
            'access_token' => 'demo_access_token',
            'webhook_verify_token' => 'demo_verify_token',
            'is_active' => true,
            'message_template' => 'demo_business_alert',
            'welcome_message' => 'Welcome to DuukaFlow WhatsApp demo mode.',
        ]);
    }

    public function ensureTemplatesForBusiness(?int $businessId = null): array
    {
        $businessId = $businessId ?? Auth::user()?->business_id;

        $templates = WhatsAppTemplate::where('business_id', $businessId)->get();

        if ($templates->isNotEmpty()) {
            return $templates->toArray();
        }

        $defaults = [
            [
                'name' => 'low_stock_alert',
                'category' => 'low_stock',
                'locale' => 'en',
                'body' => 'Stock alert for *{{product_name}}*: only {{current_stock}} units left.',
                'variables' => ['product_name', 'current_stock'],
                'status' => 'approved',
            ],
            [
                'name' => 'daily_sales_summary',
                'category' => 'daily_summary',
                'locale' => 'en',
                'body' => 'Daily summary: sales {{sales_amount}} and profit {{profit_amount}}.',
                'variables' => ['sales_amount', 'profit_amount'],
                'status' => 'approved',
            ],
            [
                'name' => 'payment_reminder',
                'category' => 'payment',
                'locale' => 'en',
                'body' => 'Hi {{customer_name}}, your payment of {{amount}} is due today.',
                'variables' => ['customer_name', 'amount'],
                'status' => 'approved',
            ],
        ];

        foreach ($defaults as $template) {
            WhatsAppTemplate::create([
                'business_id' => $businessId,
                ...$template,
            ]);
        }

        return WhatsAppTemplate::where('business_id', $businessId)->get()->toArray();
    }

    /**
     * Queue a WhatsApp message so outbound delivery is handled asynchronously.
     */
    public function queueDemoMessage(string $recipient, string $message, ?WhatsAppConfig $config = null): array
    {
        $config ??= $this->getConfigForBusiness();

        $notification = (new WhatsAppNotificationService())->buildPayload([
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

        $notification = (new WhatsAppNotificationService())->buildPayload($payload);
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

        $log = WhatsAppMessageLog::create([
            'business_id' => $config->business_id,
            'template_id' => null,
            'recipient' => $recipient,
            'channel' => 'whatsapp',
            'message_body' => $message,
            'variables' => ['business_phone' => $config->business_phone],
            'status' => $result['success'] ? 'sent' : 'failed',
            'provider_response' => $result,
            'sent_at' => $result['success'] ? now() : null,
        ]);

        return [
            'success' => true,
            'business_number' => $config->business_phone,
            'provider' => $config->provider,
            'message' => 'Demo WhatsApp message queued successfully. No live API billing is attached yet.',
            'recipient' => $recipient,
            'log_id' => $log->id,
            'dedupe_key' => $notification['dedupe_key'],
        ];
    }
}
