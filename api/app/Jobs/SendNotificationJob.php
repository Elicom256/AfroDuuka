<?php

namespace App\Jobs;

use App\Contracts\Notifications\ChannelResult;
use App\Models\NotificationDelivery;
use App\Services\Notifications\ChannelRegistry;
use App\Support\Tenant\BusinessContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Carries one reserved delivery to its transport.
 *
 * Takes the delivery id, not the model, and reloads inside the job. The row is the
 * state machine: pending -> sending -> sent/failed, and a retry has to re-read it
 * rather than trust a serialised snapshot from before the last attempt.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $deliveryId,
        public readonly string $channel,
    ) {}

    public function handle(ChannelRegistry $channels): void
    {
        // Read unscoped and before any context exists: the row itself is what names the
        // tenant, so scoping the lookup by a context would be circular. This is also why a
        // missing or wrong context cannot by itself make this job read another business's
        // row — the id comes off the queue, not off a session.
        $delivery = NotificationDelivery::withoutGlobalScopes()->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        // Everything from here runs as the delivery's own business. The channel below
        // resolves WhatsAppConfig by $delivery->business_id explicitly, so this is not
        // what makes it correct today; it is what keeps the attachment builders and any
        // query added later inside the delivery's tenant rather than the install's.
        app(BusinessContext::class)->run(
            (int) $delivery->business_id,
            fn () => $this->deliver($delivery, $channels)
        );
    }

    /**
     * The send itself, inside the delivery's own tenant.
     */
    private function deliver(NotificationDelivery $delivery, ChannelRegistry $channels): void
    {
        // A retry after a definitive failure is allowed. A retry after an ambiguous one
        // is not: the message may already be with the customer, and the status webhook
        // is the only thing that can tell us which.
        if ($delivery->isAmbiguous()) {
            Log::info('Leaving an ambiguous delivery for the status webhook', [
                'delivery_id' => $delivery->id,
            ]);

            return;
        }

        if (in_array($delivery->status, [
            NotificationDelivery::STATUS_SENT,
            NotificationDelivery::STATUS_DELIVERED,
            NotificationDelivery::STATUS_READ,
            NotificationDelivery::STATUS_SUPPRESSED,
        ], true)) {
            return;
        }

        $transport = $channels->for($this->channel);

        if ($transport === null) {
            $delivery->markSuppressed(NotificationDelivery::REASON_NO_RECIPIENT);

            return;
        }

        if (! $transport->isAvailable($delivery)) {
            $delivery->markSuppressed(
                $transport->unavailableReason($delivery) ?? NotificationDelivery::REASON_NO_RECIPIENT
            );

            return;
        }

        $delivery->forceFill([
            'status' => NotificationDelivery::STATUS_SENDING,
            'attempt_count' => $delivery->attempt_count + 1,
            'last_attempt_at' => now(),
        ])->save();

        try {
            $result = $transport->send($delivery, (array) $delivery->payload);
        } catch (Throwable $e) {
            // An exception is ambiguous by definition: we do not know whether the
            // request reached the provider.
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SENDING,
                'error_code' => 'exception',
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ])->save();

            throw $e;
        }

        $this->applyResult($delivery, $result);
    }

    private function applyResult(NotificationDelivery $delivery, ChannelResult $result): void
    {
        if ($result->accepted) {
            $delivery->forceFill([
                'status' => NotificationDelivery::STATUS_SENT,
                'provider' => $delivery->provider,
                'provider_message_id' => $result->providerMessageId,
                'sent_at' => now(),
                'error_code' => null,
                'error_message' => null,
            ])->save();

            return;
        }

        if ($result->ambiguous) {
            // Left in `sending` on purpose. See the class docblock.
            $delivery->forceFill([
                'error_code' => $result->errorCode,
                'error_message' => mb_substr((string) $result->errorMessage, 0, 1000),
            ])->save();

            return;
        }

        $delivery->markFailed(
            (string) $result->errorCode,
            (string) $result->errorMessage
        );
    }

    /**
     * A definitive rejection will not fix itself by being retried, so it is not
     * rethrown. Retrying a 4xx just burns the attempt budget on a payload that is
     * wrong.
     */
    public function failed(Throwable $e): void
    {
        Log::warning('Notification send failed', [
            'delivery_id' => $this->deliveryId,
            'channel' => $this->channel,
            'error' => $e->getMessage(),
        ]);
    }
}
