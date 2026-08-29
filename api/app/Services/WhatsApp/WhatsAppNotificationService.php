<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Str;

class WhatsAppNotificationService
{
    public function buildPayload(array $payload): array
    {
        $businessId = (int) ($payload['business_id'] ?? 0);
        $branchId = $payload['branch_id'] ?? null;
        $type = (string) ($payload['type'] ?? 'general');
        $templateKey = (string) ($payload['template_key'] ?? 'general');
        $recipientPhone = (string) ($payload['recipient_phone'] ?? '');
        $templateData = is_array($payload['template_data'] ?? null) ? $payload['template_data'] : [];

        $dedupeKey = implode(':', [
            'notification',
            $type,
            'business-' . $businessId,
            $branchId ? 'branch-' . $branchId : 'branch-none',
            $templateKey,
            md5(json_encode($templateData, JSON_THROW_ON_ERROR)),
        ]);

        return [
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'type' => $type,
            'template_key' => $templateKey,
            'recipient_phone' => $recipientPhone,
            'template_data' => $templateData,
            'dedupe_key' => $dedupeKey,
            'metadata' => [
                'generated_at' => now()->toDateTimeString(),
                'event_uuid' => (string) Str::uuid(),
            ],
        ];
    }
}
