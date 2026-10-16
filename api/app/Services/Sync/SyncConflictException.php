<?php

namespace App\Services\Sync;

use Exception;

/**
 * A domain condition that cannot be resolved by retrying the same payload —
 * a price that changed while the device was offline, stock that is no longer
 * there, a branch conflict. Reported to the client as `conflict` rather than
 * `failed` so the frontend can surface it for reconciliation instead of
 * re-queueing an operation that will never succeed unchanged.
 *
 * The operation row is still persisted as `failed` with this error code: the
 * business transaction rolled back, and a human decision is required before the
 * operation should run again.
 */
class SyncConflictException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function make(string $errorCode, string $message, array $details = []): static
    {
        return new static($errorCode, $message, $details);
    }
}
