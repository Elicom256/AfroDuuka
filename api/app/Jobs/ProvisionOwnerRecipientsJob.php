<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\User;
use App\Services\Notifications\RecipientProvisioner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Create the owner's notification recipients after a business is registered.
 *
 * This used to be a try/catch inside BusinessService::create() that logged and moved
 * on. That is the right trade for the transaction — a missing notification preference
 * row must not cost the owner their business — but logging alone means a transient
 * failure stays failed until someone notices the log, and the business is then
 * permanently unreachable by every channel.
 *
 * Queuing it moves the work off the registration request and gives it a retry
 * schedule, so the same call that used to be a one-shot silent failure is now a
 * durable attempt. Dispatched with afterCommit() so the business row exists by the
 * time the worker runs it.
 */
class ProvisionOwnerRecipientsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Back off before the first three retries so a database blip or a lock held by the
     * registering request has a chance to clear.
     */
    public int $backoff = 30;

    public int $tries = 5;

    /**
     * @param  int  $businessId  Resolved fresh on each attempt; passing the model would
     *                           serialise a stale instance across a retry.
     * @param  int|null  $ownerId  Optional so the provisioner can still find the owner by
     *                             business_id + admin role when this is null.
     */
    public function __construct(
        public int $businessId,
        public ?int $ownerId = null
    ) {}

    public function handle(RecipientProvisioner $provisioner): void
    {
        $business = Business::find($this->businessId);

        if ($business === null) {
            // The transaction that created it rolled back, or the row was removed
            // before the job ran. Retrying cannot fix either, so stop quietly.
            Log::warning('Skipping owner recipient provisioning, business no longer exists', [
                'business_id' => $this->businessId,
            ]);

            return;
        }

        $owner = $this->ownerId !== null ? User::find($this->ownerId) : null;

        $result = $provisioner->ensureOwner($business, $owner);

        if ($result['skipped'] !== []) {
            Log::info('Notification recipients skipped channels at registration', [
                'business_id' => $business->id,
                'skipped' => $result['skipped'],
            ]);
        }
    }
}
