<?php

namespace App\Notifications\Channels;

use App\Contracts\Notifications\ChannelResult;
use App\Contracts\Notifications\NotificationChannel;
use App\Models\NotificationDelivery;

/**
 * Placeholder email transport, so the pipeline is exercisable before SES exists.
 *
 * Logs the rendered values rather than the body. Receipts and monthly reports carry
 * customer names and transaction amounts, and those do not belong in an info-level log
 * line.
 *
 * The real SesMailChannel replaces this in Stage 2. The interface is the same, so
 * nothing upstream changes.
 */
class LogMailChannel implements NotificationChannel
{
    public function name(): string
    {
        return 'email';
    }

    public function isAvailable(NotificationDelivery $delivery): bool
    {
        return true;
    }

    public function unavailableReason(NotificationDelivery $delivery): ?string
    {
        return null;
    }

    public function send(NotificationDelivery $delivery, array $parameters): ChannelResult
    {
        $address = $delivery->recipient_address;

        if ($address === null) {
            return ChannelResult::rejected('no_address', 'Delivery has no recipient address.');
        }

        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return ChannelResult::rejected('bad_address', 'Recipient is not a valid email address.');
        }

        logger()->info('Notification email (LogMailChannel)', [
            'delivery_id' => $delivery->id,
            'type' => $delivery->type,
            'category' => $delivery->category,
            // The address is recorded in notification_deliveries already; the rendered
            // values are logged because they are what the body is built from, and the
            // body itself is not customer data we want in the log.
            'parameter_keys' => array_keys($parameters),
        ]);

        return ChannelResult::accepted('log-'.$delivery->id);
    }
}
