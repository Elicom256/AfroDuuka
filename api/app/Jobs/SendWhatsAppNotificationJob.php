<?php

namespace App\Jobs;

use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue-driven WhatsApp sender stub.
 * This keeps the architecture production-ready while the paid provider is not active.
 */
class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $businessId,
        public string $recipient,
        public string $message,
        public ?int $configId = null,
    ) {
    }

    public function handle(): void
    {
        $config = WhatsAppConfig::find($this->configId) ?? WhatsAppConfig::where('business_id', $this->businessId)->first();

        $log = WhatsAppMessageLog::create([
            'business_id' => $this->businessId,
            'template_id' => null,
            'recipient' => $this->recipient,
            'channel' => 'whatsapp',
            'message_body' => $this->message,
            'variables' => ['provider' => $config?->provider ?? 'demo'],
            'status' => 'queued',
            'provider_response' => ['mode' => 'demo'],
        ]);

        Log::info('Demo WhatsApp notification queued', [
            'business_id' => $this->businessId,
            'recipient' => $this->recipient,
            'message' => $this->message,
            'config_id' => $this->configId,
        ]);

        $log->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_response' => [
                'mode' => 'demo',
                'business_phone' => $config?->business_phone ?? '+256731794401',
                'status' => 'sent',
                'warning' => 'No paid WhatsApp API configured yet.',
            ],
        ]);
    }
}
