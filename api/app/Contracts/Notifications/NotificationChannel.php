<?php

namespace App\Contracts\Notifications;

use App\Models\NotificationDelivery;

/**
 * One outbound transport. Email and WhatsApp implement this identically, which is
 * what lets the dispatcher stay channel-agnostic: it resolves recipients, renders a
 * template and reserves a dedupe key without knowing which transport will carry it.
 *
 * Implementations must not decide policy. Mandatory-vs-preference, dedupe and
 * recipient eligibility are settled before a channel is called, so that WhatsApp and
 * email cannot drift apart on whether something was allowed to be sent.
 */
interface NotificationChannel
{
    /**
     * The channel key, matching a key in config('notifications.catalogue.*.channels').
     */
    public function name(): string;

    /**
     * Whether this transport is usable for this delivery, e.g. SES configured, WhatsApp
     * config active. A false here suppresses the send with a reason rather than
     * throwing, because "we cannot send this right now" is not an error.
     *
     * Takes the delivery rather than reading ambient state on purpose. A transport
     * needs to know which tenant it is sending for, and inside a queued job the tenant
     * is not in BusinessContext — it is on the row. Reading request() or Auth here
     * would work in a web request and quietly do nothing in a worker.
     */
    public function isAvailable(NotificationDelivery $delivery): bool;

    /**
     * Why the channel is unavailable, for the delivery log's suppressed_reason.
     */
    public function unavailableReason(NotificationDelivery $delivery): ?string;

    /**
     * Hand the message to the provider.
     *
     * Must not throw for an expected rejection. A definitive refusal (bad address,
     * template mismatch, 4xx) is returned as a failed result; only an ambiguous
     * outcome is signalled with isAmbiguous() so the row is left in `sending` for the
     * webhook to resolve rather than retried into a double-send.
     *
     * @param  array<string, mixed>  $parameters  Rendered values, already ordered to
     *                                            match the template's declared variables.
     */
    public function send(NotificationDelivery $delivery, array $parameters): ChannelResult;
}
