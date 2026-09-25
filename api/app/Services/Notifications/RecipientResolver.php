<?php

namespace App\Services\Notifications;

use App\Models\Business;
use App\Models\NotificationRecipient;
use Illuminate\Support\Collection;

/**
 * Works out who should receive a notification.
 *
 * The plan's resolution order, and the reason for it — each step is a fact we know,
 * and the next step is a progressively weaker guess:
 *
 *   1. an explicitly supplied address        — the caller knows something we don't
 *   2. a stored recipient for this channel   — configured by the business
 *   3. the business's own phone or email     — the sender config
 *   4. nothing, and log no_recipient         — never invent one
 *
 * Step 4 is the important one. The previous implementation fell back to a hard-coded
 * demo number, which meant a business with no WhatsApp configured silently sent its
 * stock alerts to DuukaFlow's own phone.
 */
class RecipientResolver
{
    public function __construct(
        private readonly AddressNormaliser $normaliser,
    ) {}

    /**
     * Stored recipients for a business and channel, honouring the plan's scope
     * fallback: branch-level first, then business-level.
     *
     * A branch alert still has to reach someone when the business has only set up an
     * owner at business level, which is the common case. Skipping straight to the
     * business phone number instead would mean the owner's own stored opt-outs and
     * preferences were bypassed.
     *
     * @return Collection<int, NotificationRecipient>
     */
    public function stored(int $businessId, string $channel, ?int $businessBranchId = null): Collection
    {
        if ($businessBranchId !== null) {
            $branchLevel = $this->atScope($businessId, $channel, $businessBranchId);

            if ($branchLevel->isNotEmpty()) {
                return $branchLevel;
            }
        }

        return $this->atScope($businessId, $channel, null);
    }

    /**
     * @return Collection<int, NotificationRecipient>
     */
    private function atScope(int $businessId, string $channel, ?int $businessBranchId): Collection
    {
        return NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->whereNotNull('verified_at')
            ->when(
                $businessBranchId !== null,
                fn ($query) => $query->where('business_branch_id', $businessBranchId),
                fn ($query) => $query->whereNull('business_branch_id')
            )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * The address to use, or null when there is genuinely nobody to tell.
     *
     * @param  array<string, mixed>  $context  Free-form labels for the log message,
     *                                         e.g. ['branch' => 'Nakasero'].
     */
    public function resolveAddress(int $businessId, string $channel, ?int $businessBranchId = null, array $context = []): ?string
    {
        $stored = $this->stored($businessId, $channel, $businessBranchId);

        foreach ($stored as $recipient) {
            $address = $this->normaliseForChannel($recipient->address, $channel);

            if ($address !== null) {
                return $address;
            }
        }

        return $this->fallbackFromBusiness($businessId, $channel, $context);
    }

    /**
     * An explicit address wins over everything, because it came from the record the
     * event was about — a customer's email on a quotation, for instance.
     */
    public function explicitAddress(string $channel, mixed $raw): ?string
    {
        return $this->normaliseForChannel($raw, $channel);
    }

    /**
     * The business's own contact details. This is the weakest step: it is the sender
     * config being reused as a recipient, which is right for an owner-level business
     * notice and wrong for a branch alert.
     */
    private function fallbackFromBusiness(int $businessId, string $channel, array $context = []): ?string
    {
        $business = Business::withoutGlobalScopes()->find($businessId);

        if ($business === null) {
            return null;
        }

        $raw = $channel === NotificationRecipient::CHANNEL_WHATSAPP
            ? $business->phone
            : $business->email;

        $address = $this->normaliseForChannel($raw, $channel);

        if ($address === null && $raw !== null && $raw !== '') {
            // Present but unusable. Worth surfacing: it means the business has set a
            // value we cannot send to, which is a data problem they can fix.
            report(sprintf(
                'Notification recipient %s for business %d could not be normalised (raw: %s). %s',
                $channel,
                $businessId,
                is_scalar($raw) ? (string) $raw : get_debug_type($raw),
                $context === [] ? '' : 'Context: '.json_encode($context)
            ));
        }

        return $address;
    }

    private function normaliseForChannel(mixed $raw, string $channel): ?string
    {
        return match ($channel) {
            NotificationRecipient::CHANNEL_WHATSAPP => $this->normaliser->phone($raw),
            NotificationRecipient::CHANNEL_EMAIL => $this->normaliser->email($raw),
            default => null,
        };
    }
}
