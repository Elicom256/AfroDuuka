<?php

namespace App\Services\Notifications;

use App\Models\Business;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Creates the notification recipients a new business starts with.
 *
 * Called at business creation. Without it the dispatcher has nothing to resolve, and
 * since its last resort is "give up and log no_recipient" rather than guessing an
 * address, every notification for a new business would be suppressed — the owner would
 * register and then hear nothing about their own stock or orders, with no indication of
 * why.
 *
 * Both channels are seeded from the owner's own details. The owner is the one person
 * guaranteed to exist and to want these messages, so they are the safe default; admins
 * and managers are added later by whoever manages recipients.
 */
class RecipientProvisioner
{
    public function __construct(
        private readonly AddressNormaliser $normaliser,
        private readonly NotificationCatalogue $catalogue,
    ) {}

    /**
     * Seed the owner on both channels. Safe to re-run.
     *
     * Idempotency matters more than it looks: a retry of the registration request, or
     * an owner changing their phone number and re-triggering this, must not blow up on
     * the unique (business_id, branch_id, channel, address) index. Re-running updates
     * the existing row rather than inserting a second one.
     *
     * @return array{created: int, skipped: array<int, string>, labels: array<int, string>}
     */
    public function ensureOwner(Business|int $business, ?User $owner = null): array
    {
        $business = $business instanceof Business ? $business : Business::withoutGlobalScopes()->findOrFail($business);

        $owner ??= $this->ownerUserFor($business);

        // Branch level, deliberately. An owner is not a branch recipient, and a
        // branch_id here would hide the owner from business-scoped notifications and
        // leave the branch with no recipient of its own.
        $branchId = null;

        $result = ['created' => 0, 'skipped' => [], 'labels' => []];

        foreach ($this->normalisedAddresses($business, $owner) as $channel => $address) {
            if ($address === null) {
                $result['skipped'][] = $channel;

                Log::warning('Notification recipient skipped at business creation', [
                    'business_id' => $business->id,
                    'channel' => $channel,
                    'reason' => 'address did not normalise',
                ]);

                continue;
            }

            $existing = NotificationRecipient::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('business_branch_id', $branchId)
                ->where('channel', $channel)
                ->where('address', $address)
                ->first();

            if ($existing !== null) {
                continue;
            }

            // The unique index includes the address, so a changed phone number means a
            // *new* row rather than an update. That is intentional: the old address may
            // already be verified and have delivery history behind it, and rewriting it
            // in place would attribute those deliveries to the new number. The stale
            // row is deactivated rather than deleted so that history stays readable.
            $this->deactivateSuperseded($business, $channel, $address);

            NotificationRecipient::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'business_branch_id' => $branchId,
                'user_id' => $owner?->id,
                'label' => NotificationRecipient::LABEL_OWNER,
                'channel' => $channel,
                'address' => $address,
                // Mandatory categories only, per the plan. A business that has just
                // registered has expressed no interest in inventory or marketing mail,
                // so non-mandatory categories are opt-in from here, not opt-out.
                'categories' => $this->catalogue->mandatoryCategories(),
                'is_active' => true,
                // Null on purpose: verified_at is set by the first successful send, not
                // by signup. A recipient nobody has ever reached is not a recipient.
                'verified_at' => null,
            ]);

            $result['created']++;
            $result['labels'][] = $channel;
        }

        return $result;
    }

    /**
     * The business owner, i.e. the user on the executive role.
     *
     * The user's own details are preferred over the business's throughout. At
     * registration they are the same value, but if an owner later changes their profile
     * email the recipient should follow them, not keep mailing the address typed into
     * the business form months earlier.
     *
     * Falls back to the first user on the business because "who owns this" is not
     * reliably derivable from the users table here, and a null user_id still yields a
     * working recipient — the link to a login is a convenience, not a requirement.
     */
    private function ownerUserFor(Business $business): ?User
    {
        $user = User::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereHas('role', fn ($q) => $q->where('name', 'Executive'))
            ->first();

        return $user ?? User::withoutGlobalScopes()->where('business_id', $business->id)->first();
    }

    /**
     * Normalised address per channel, or null where there is nothing usable.
     *
     * Normalising on write is what makes the unique index meaningful: without it,
     * "0772 123456" and "+256772123456" would be two recipient rows for one person,
     * and they would diverge the moment the owner's number was retyped in another
     * format. A null is not a failure to log loudly about — a business registered with
     * no phone, or with one that cannot be normalised, is a real and common case, and
     * the other channel still works.
     *
     * @return array<string, string|null>
     */
    private function normalisedAddresses(Business $business, ?User $owner): array
    {
        return [
            NotificationRecipient::CHANNEL_EMAIL => $this->normaliser->email(
                $owner?->email ?: $business->email
            ),
            NotificationRecipient::CHANNEL_WHATSAPP => $this->normaliser->phone(
                $owner?->phone ?: $business->phone
            ),
        ];
    }

    /**
     * Deactivate any other active recipient on this channel whose address differs.
     *
     * Scoped to the owner label: a manager the business added deliberately must not be
     * deactivated because the owner's number changed.
     */
    private function deactivateSuperseded(Business $business, string $channel, string $address): void
    {
        NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('label', NotificationRecipient::LABEL_OWNER)
            ->where('channel', $channel)
            ->where('address', '!=', $address)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
