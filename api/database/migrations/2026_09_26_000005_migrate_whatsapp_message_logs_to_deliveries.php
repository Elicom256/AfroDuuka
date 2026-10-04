<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whats_app_message_logs')) {
            return;
        }

        DB::table('whats_app_message_logs')->orderBy('id')->chunk(500, function ($logs) {
            foreach ($logs as $log) {
                // A legacy row may predate dedupe keys; synthesise a stable one so the
                // backfill cannot collide with itself on the unique index.
                $dedupeKey = $log->dedupe_key ?: 'legacy:whatsapp:log-'.$log->id;

                DB::table('notification_deliveries')->insertOrIgnore([
                    'business_id' => $log->business_id,
                    'business_branch_id' => null,
                    'channel' => $log->channel ?: 'whatsapp',
                    'category' => 'system',
                    'type' => 'legacy.whatsapp_message',
                    'template_key' => null,
                    'recipient_id' => null,
                    'recipient_address' => $log->recipient,
                    'status' => $this->mapStatus($log->status),
                    'provider' => data_get($log->provider_response, 'provider'),
                    'provider_message_id' => null,
                    'attempt_count' => 1,
                    'last_attempt_at' => $log->created_at,
                    'payload' => json_encode([
                        'template_id' => $log->template_id,
                        'variables' => $log->variables,
                    ]),
                    'error_code' => $log->error_code,
                    'error_message' => null,
                    'sent_at' => $log->sent_at,
                    'delivered_at' => $log->delivered_at,
                    'read_at' => $log->read_at,
                    'failed_at' => $log->status === 'failed' ? $log->updated_at : null,
                    'suppressed_at' => null,
                    'dedupe_key' => $dedupeKey,
                    'meta_template_name' => null,
                    'meta_template_language' => null,
                    'is_mandatory' => false,
                    'suppressed_reason' => null,
                    'created_at' => $log->created_at,
                    'updated_at' => $log->updated_at,
                ]);
            }
        });
    }

    /**
     * Map the legacy status vocabulary onto the cross-channel one. Unknown values
     * become 'sent' rather than being dropped, so no history is silently lost.
     */
    private function mapStatus(?string $status): string
    {
        return match ($status) {
            'queued', 'pending' => 'pending',
            'sending' => 'sending',
            'sent' => 'sent',
            'delivered' => 'delivered',
            'read' => 'read',
            'failed' => 'failed',
            'suppressed' => 'suppressed',
            default => 'sent',
        };
    }

    public function down(): void
    {
        DB::table('notification_deliveries')
            ->where('type', 'legacy.whatsapp_message')
            ->delete();
    }
};
