<?php

namespace App\Services\Notifications;

use App\Models\NotificationDelivery;
use App\Models\NotificationRecipient;
use App\Models\NotificationSubscription;

/**
 * Decides whether one address may receive one notification.
 *
 * The split is the whole point: a billing notice is not suppressible, and everything
 * else is. Someone who has muted inventory alerts must still hear that their card
 * was declined, because that is the notification they would act on.
 *
 * Mandatory is a property of the notification, not of the recipient, so it is read
 * from the catalogue's category rather than from config('notifications.email...
 * .transactional_categories'). One list, no chance of the two disagreeing.
 */
class PreferenceResolver
{
    public function __construct(
        private readonly NotificationCatalogue $catalogue,
    ) {}

    /**
     * @return array{allowed: bool, reason: string|null}
     */
    public function check(string $type, string $channel, string $address, ?NotificationRecipient $recipient = null): array
    {
        $category = $this->catalogue->categoryFor($type);

        if ($this->isMandatory($category)) {
            return ['allowed' => true, 'reason' => null];
        }

        // A stored recipient carries its own per-category allow-list.
        if ($recipient !== null && ! $recipient->canReceive($category)) {
            return ['allowed' => false, 'reason' => NotificationDelivery::REASON_OPTED_OUT];
        }

        if ($channel === NotificationRecipient::CHANNEL_EMAIL) {
            $subscription = NotificationSubscription::forEmail($address);

            if (! $subscription->isSubscribedTo($category)) {
                return ['allowed' => false, 'reason' => NotificationDelivery::REASON_OPTED_OUT];
            }
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function allows(string $type, string $channel, string $address, ?NotificationRecipient $recipient = null): bool
    {
        return $this->check($type, $channel, $address, $recipient)['allowed'];
    }

    /**
     * Non-suppressible categories, delegated to the catalogue.
     *
     * Deliberately not a hardcoded list: an earlier version read
     * config('notifications.email.transactional_categories'), which was copied from
     * the brief and had drifted from the catalogue's own mandatory flags. The drift was
     * not cosmetic — the system and order categories were mandatory to the dispatcher
     * and not to this check, so a mandatory notification could be dropped here as
     * opted-out. The config key has been removed rather than left as a trap.
     */
    public function isMandatory(string $category): bool
    {
        return $this->catalogue->isMandatoryCategory($category);
    }
}
