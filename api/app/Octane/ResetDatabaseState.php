<?php

namespace App\Octane;

use Throwable;

/**
 * Runs after every Octane operation (request, task, tick).
 *
 * Octane keeps one application instance — and therefore one PDO connection —
 * alive across operations. If a previous operation ever ended while a
 * transaction was still open (worker interrupted, pooler anomaly), the next
 * operation would inherit an aborted transaction and fail its very first
 * statement with SQLSTATE 25P02.
 *
 * Rolling back makes the close clean when the counter still knows about the
 * transaction; disconnecting guarantees a fresh server connection either way,
 * because closing the socket makes PostgreSQL discard the backend's state.
 */
class ResetDatabaseState
{
    /**
     * Handle the event.
     *
     * @param  mixed  $event
     */
    public function handle($event): void
    {
        if (! $event->sandbox->resolved('db')) {
            return;
        }

        foreach ($event->sandbox->make('db')->getConnections() as $connection) {
            try {
                // rollBack() decrements one level per call; loop until no
                // transaction remains. It also clears the
                // DatabaseTransactionsManager's pending after-commit callbacks.
                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            } catch (Throwable) {
                // The transaction may already be aborted server-side; the
                // disconnect below discards the backend regardless.
            }

            $connection->disconnect();
        }
    }
}
