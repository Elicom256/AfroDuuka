<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Settles deliveries that SES never confirmed, and reports on suppressions.
 *
 * Bounces and complaints are applied by the webhook the moment they arrive — queueing
 * them would only add latency to a suppression, and the address keeps receiving mail in
 * the meantime. This is the backstop, not the primary path.
 *
 * A send that timed out is deliberately left in `sending`, because the message may have
 * landed and re-sending it double-delivers to the customer. If SES also never confirms
 * it, that row stays `sending` forever, which is worse than the ambiguity it was
 * protecting: it is not counted as failed, so the attempt budget never advances, and it
 * never appears as a failure to anyone reading the delivery log. This marks those
 * rows `unconfirmed` once the grace period passes. It does not retry them — that risk
 * is the whole reason they were parked.
 */
/**
 * Settles SES sends the provider never confirmed, and reports bounces.
 *
 * The one job here that is deliberately cross-tenant, and it must stay that way. A send
 * can be left in `sending` because the provider never acknowledged it, and the sweep is
 * the backstop that eventually gives up on it — for every business at once, which is the
 * whole point of a backstop. Wrapping handle() in BusinessContext::run() would AND a
 * business_id onto the query below and settle exactly one tenant's stranded rows while
 * reporting success, which is worse than not running at all.
 *
 * So the context is deliberately absent here, and the per-row writes below need none:
 * markFailed() forceFills and saves an already-loaded model, which re-queries nothing, so
 * no scope is ever applied. If a future change makes this sweep query or write anything
 * per-delivery, it must wrap that part in run($delivery->business_id, ...) rather than
 * narrowing the sweep.
 */
class ProcessSesSuppressionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $grace = (int) config('notifications.unconfirmed_grace_minutes', 60);

        $stuck = NotificationDelivery::where('status', NotificationDelivery::STATUS_SENDING)
            ->where('created_at', '<', now()->subMinutes($grace))
            ->get();

        foreach ($stuck as $delivery) {
            $delivery->markFailed(
                'unconfirmed',
                'SES never confirmed this send after '.$grace.' minutes.'
            );
        }

        $settled = $stuck->count();

        if ($settled > 0) {
            Log::info('Settled unconfirmed SES sends', [
                'count' => $settled,
                'grace_minutes' => $grace,
            ]);
        }

        $this->reportSuppressions();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessSesSuppressionsJob failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Surfaces how many addresses the provider has told us are undeliverable.
     *
     * A silent failure here is the risk: addresses accumulate, nobody is emailed, and
     * the first sign of it is a customer asking why a quote never arrived. Counting
     * them on a schedule means the number is visible before that happens.
     */
    private function reportSuppressions(): void
    {
        $since = now()->subMinutes(15);

        $bounced = NotificationDelivery::where('error_code', 'bounced')
            ->where('failed_at', '>=', $since)
            ->count();

        $complained = NotificationDelivery::where('error_code', 'complained')
            ->where('failed_at', '>=', $since)
            ->count();

        if ($bounced > 0 || $complained > 0) {
            Log::info('SES suppressions in the last 15 minutes', [
                'bounced' => $bounced,
                'complained' => $complained,
            ]);
        }
    }
}
