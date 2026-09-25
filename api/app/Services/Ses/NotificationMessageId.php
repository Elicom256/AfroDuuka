<?php

namespace App\Services\Ses;

use App\Models\NotificationDelivery;

/**
 * The Message-ID a notification email is sent under, and the key SES events come back on.
 *
 * SES reports a bounce or complaint against `mail.messageId`, which is the Message-ID
 * header of the message we sent — it does not echo an id of ours. So the correlation key
 * has to be built into that header at send time, and it has to be parseable.
 *
 * Using the delivery's own id is what makes it parseable: a random id would have to be
 * stored and looked up, and the alternative everyone reaches for first — matching on
 * recipient address and timestamp — is a guess, and would attach a bounce to whichever
 * delivery happened to be most recent.
 */
final class NotificationMessageId
{
    /**
     * @return string The value SES will report back, without angle brackets.
     */
    public static function forDelivery(NotificationDelivery $delivery): string
    {
        return self::make((int) $delivery->id);
    }

    public static function make(int $deliveryId): string
    {
        return 'notification-'.$deliveryId.'@'.self::domain();
    }

    /**
     * The delivery id carried by a Message-ID, or null if this is not one of ours.
     *
     * A foreign Message-ID is normal, not an error: SES may report on mail sent by
     * something else sharing the topic, and older rows predate this scheme. Returning
     * null lets the caller record the event and move on rather than guessing.
     */
    public static function deliveryIdFor(string $messageId): ?int
    {
        if (! preg_match('/^notification-(\d+)@/', trim($messageId, '<> '), $matches)) {
            return null;
        }

        $id = (int) $matches[1];

        return $id > 0 ? $id : null;
    }

    /**
     * Derived from the sending domain so it matches the From identity, which is what
     * makes the header legitimate to SES and readable to a human debugging a bounce.
     */
    private static function domain(): string
    {
        $configured = (string) config('notifications.email.message_id_domain');

        if ($configured !== '') {
            return $configured;
        }

        $from = (string) config('notifications.email.from_address');

        if (str_contains($from, '@')) {
            return substr(strrchr($from, '@') ?: '', 1);
        }

        return 'duukaflow.com';
    }
}
