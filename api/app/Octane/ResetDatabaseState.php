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
 * transaction. When the abort was inherited, the counter is zero and knows
 * nothing, so a raw ROLLBACK is sent as well: through a pooler the backend
 * does not die with our socket, and only ROLLBACK returns it to the pool in
 * a usable state. Disconnecting then guarantees a fresh server connection
 * for the next operation either way.
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
                // raw ROLLBACK and disconnect below take over.
            }

            // Clears an inherited abort the PHP counter cannot see. Outside a
            // transaction PostgreSQL replies with a warning, never an error.
            try {
                $pdo = $connection->getRawPdo();

                if ($pdo instanceof \PDO) {
                    $pdo->exec('ROLLBACK');
                }
            } catch (Throwable) {
                // A dead PDO cannot be healed; disconnecting drops it.
            }

            $connection->disconnect();
        }
    }
}
