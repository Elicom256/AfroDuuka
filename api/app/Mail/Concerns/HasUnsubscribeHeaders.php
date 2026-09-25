<?php

namespace App\Mail\Concerns;

use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\URL;

/**
 * Adds RFC 8058 one-click unsubscribe headers to preference-checked mail.
 *
 * Two rules, both of which are compliance requirements rather than preferences:
 *
 *   - Bulk and preference-checked mail MUST carry List-Unsubscribe. Without it a
 *     sending domain accumulates spam complaints and gets its SES reputation
 *     destroyed, which eventually stops delivery for every customer on it.
 *   - Transactional mail MUST NOT carry it. A billing notice with an unsubscribe
 *     header is telling the recipient to stop receiving their billing notices, and
 *     mail clients will act on it. The mandate to send those is the purchase.
 *
 * The one-click variant matters because Apple Mail and Outlook both act on
 * List-Unsubscribe-Post directly, with no user interaction. A header pointing only at
 * a page the user has to visit is treated as a weak signal by those clients.
 */
trait HasUnsubscribeHeaders
{
    /**
     * Transactional mail carries no unsubscribe headers.
     */
    public function withoutUnsubscribe(): static
    {
        $this->suppressUnsubscribe = true;

        return $this;
    }

    /**
     * The signed URL the headers point at.
     *
     * Signed rather than a stored token because notification_subscriptions.token holds
     * a hash, and a hash cannot be put back into an email. The signature carries the
     * same authority without storing anything reversible, and it cannot be edited to
     * unsubscribe someone else.
     */
    protected function unsubscribeUrl(NotificationDelivery $delivery): string
    {
        return URL::signedRoute('notifications.unsubscribe', ['delivery' => $delivery->id]);
    }
}
