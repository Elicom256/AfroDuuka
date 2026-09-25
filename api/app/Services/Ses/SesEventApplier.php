<?php

namespace App\Services\Ses;

use App\Models\NotificationDelivery;
use App\Models\NotificationRecipient;
use App\Models\NotificationSubscription;
use Illuminate\Support\Facades\Log;

/**
 * Applies one SES event to the delivery it belongs to.
 *
 * The rule that matters: a bounce must be attributed to a specific delivery, or not
 * acted on at all. Guessing from recipient address and timestamp would attach a bounce
 * to whichever delivery happened to be most recent, which means suppressing a good
 * address because of an unrelated message and — worse — leaving the actually-broken
 * address deliverable. So an unrecognisable message id records the event and stops.
 */
class SesEventApplier
{
    /**
     * Delivery states that a provider event may still legitimately move. A delivery
     * already marked failed or suppressed by its own send path has been decided, and a
     * late webhook must not resurrect it.
     */
    private const SETTLED = ['failed', 'suppressed'];

    /**
     * @param  array<string, mixed>  $event
     */
    public function apply(array $event): ?NotificationDelivery
    {
        $type = (string) ($event['notificationType'] ?? '');

        $messageId = (string) (($event['mail']['messageId'] ?? null));

        if ($messageId === '') {
            Log::info('SES event had no messageId', ['type' => $type]);

            return null;
        }

        $deliveryId = NotificationMessageId::deliveryIdFor($messageId);

        if ($deliveryId === null) {
            // Not one of ours — a shared topic, or mail sent before Message-IDs were
            // stamped. Nothing to suppress, and no safe way to guess.
            Log::info('SES event did not reference a notification delivery', [
                'type' => $type,
                'message_id' => $messageId,
            ]);

            return null;
        }

        $delivery = NotificationDelivery::find($deliveryId);

        if ($delivery === null) {
            Log::warning('SES event referenced an unknown delivery', [
                'type' => $type,
                'delivery_id' => $deliveryId,
            ]);

            return null;
        }

        return match ($type) {
            'Bounce' => $this->bounced($delivery, $event),
            'Complaint' => $this->complained($delivery, $event),
            'Delivery' => $this->delivered($delivery),
            'Open', 'Click' => $this->opened($delivery),
            'Send' => $delivery,
            default => $delivery,
        };
    }

    /**
     * A hard bounce means the address cannot receive mail. Soft bounces are transient
     * (mailbox full, greylisting) and must not suppress a real customer, so only
     * `Permanent` is acted on.
     */
    private function bounced(NotificationDelivery $delivery, array $event): NotificationDelivery
    {
        $bounceType = (string) (($event['bounce']['bounceType'] ?? ''));

        if ($bounceType !== 'Permanent') {
            Log::info('Ignoring soft bounce', [
                'delivery_id' => $delivery->id,
                'bounce_type' => $bounceType,
            ]);

            return $delivery;
        }

        $this->settle($delivery, 'bounced');

        $this->deactivateRecipient($delivery, 'hard_bounced');

        return $delivery;
    }

    /**
     * A complaint is a spam report. That is a stronger signal than a bounce and is
     * treated as an opt-out the customer asked for, so it is not silently undone.
     */
    private function complained(NotificationDelivery $delivery, array $event): NotificationDelivery
    {
        $this->settle($delivery, 'complained');

        $this->deactivateRecipient($delivery, 'complained');

        $address = $delivery->recipient_address;

        if (filled($address)) {
            $subscription = NotificationSubscription::forEmail($address);
            $subscription->forceFill(['unsubscribed_at' => now()])->save();
        }

        return $delivery;
    }

    private function delivered(NotificationDelivery $delivery): NotificationDelivery
    {
        if (in_array($delivery->status, self::SETTLED, true)) {
            return $delivery;
        }

        $delivery->forceFill([
            'status' => NotificationDelivery::STATUS_SENT,
            'delivered_at' => now(),
        ])->save();

        return $delivery;
    }

    /**
     * Opens and clicks are advisory. Recorded when we can, never allowed to resurrect a
     * failed delivery — an open hours after a hard bounce is common enough (a webmail
     * client fetching a cached copy) that treating it as proof of delivery would undo
     * the suppression.
     */
    private function opened(NotificationDelivery $delivery): NotificationDelivery
    {
        if (in_array($delivery->status, self::SETTLED, true)) {
            return $delivery;
        }

        $delivery->forceFill([
            'read_at' => $delivery->read_at ?? now(),
        ])->save();

        return $delivery;
    }

    /**
     * Marks the delivery settled and stale bounces out of the retry path.
     */
    private function settle(NotificationDelivery $delivery, string $reason): void
    {
        $delivery->forceFill([
            'status' => NotificationDelivery::STATUS_FAILED,
            'error_code' => $reason,
            'error_message' => 'SES reported '.$reason,
            'failed_at' => $delivery->failed_at ?? now(),
        ])->save();

        Log::info('SES event suppressed a delivery', [
            'delivery_id' => $delivery->id,
            'business_id' => $delivery->business_id,
            'reason' => $reason,
        ]);
    }

    /**
     * Stops future sends to an address the provider has told us cannot receive them.
     *
     * Deactivating the recipient rows is what does the work: the resolver only returns
     * active, verified rows, so a bounced address stops generating new deliveries
     * rather than accumulating failures.
     */
    private function deactivateRecipient(NotificationDelivery $delivery, string $reason): void
    {
        $address = $delivery->recipient_address;

        if (blank($address)) {
            return;
        }

        $recipients = NotificationRecipient::where('business_id', $delivery->business_id)
            ->where('address', $address)
            ->where('is_active', true)
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->forceFill([
                'is_active' => false,
                'deactivated_reason' => $reason,
                'deactivated_at' => now(),
            ])->save();
        }

        if ($recipients->isEmpty()) {
            Log::info('SES event had no active recipient to deactivate', [
                'delivery_id' => $delivery->id,
                'reason' => $reason,
            ]);
        }
    }
}
