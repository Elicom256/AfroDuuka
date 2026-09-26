<?php

namespace App\Contracts\Notifications;

use App\Models\NotificationDelivery;

/**
 * Builds one file to be attached to a notification email.
 *
 * Builders render from the delivery's own stored payload rather than re-querying the
 * business tables, for the same reason NotificationDelivery::values() exists: a delivery
 * row is a record of what was attempted, and an attachment rendered from live data would
 * not match the body beside it if anything changed between the reserve and the send.
 */
interface AttachmentBuilder
{
    /**
     * The name the catalogue's `attachments` list refers to this builder by.
     */
    public function name(): string;

    /**
     * @return array{filename: string, content: string, mime: string}|null
     *                                                                     Null when there is
     *                                                                     nothing to attach.
     *                                                                     The email is still
     *                                                                     sent, body and all.
     */
    public function build(NotificationDelivery $delivery): ?array;
}
