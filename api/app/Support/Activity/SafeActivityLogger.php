<?php

namespace App\Support\Activity;

use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Throwable;

/**
 * An activity write is evidence, not the business action itself.
 *
 * Every model that uses the LogsActivity trait calls Spatie's ActivityLogger on save.
 * If that insert throws — a saturated disk, a missing table, a bad property payload —
 * the exception would ride back out through the model save and turn a completed sale,
 * expense or payment into a 500 that the operator reads as "nothing happened", even
 * though nothing was rolled back. The audit trail must never be the reason a business
 * action fails.
 *
 * So the write is attempted and, if it fails, reported to the application log and
 * dropped. The business action stands; support can still see that the audit entry was
 * lost, and the request_id on the log line ties it to the request that caused it.
 */
class SafeActivityLogger extends ActivityLogger
{
    public function log(string $description): ?ActivityContract
    {
        try {
            return parent::log($description);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
