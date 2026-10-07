<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Recovery from an inherited aborted transaction (SQLSTATE 25P02).
 *
 * A 25P02 that reaches this middleware means the connection is carrying an
 * aborted transaction: either it opened the request that way (a pooled
 * connection poisoned by another client), or this request's own work was
 * doomed by an earlier failure. In both cases nothing this request wrote can
 * commit — every statement of an aborted transaction fails — so rolling back,
 * dropping the connection and replaying the request is safe even for the
 * non-idempotent POS checkout. That argument holds for every replay, so the
 * middleware retries up to three times: each poisoned pooled connection it
 * meets is healed on the way out, which drains a pool poisoned by an old
 * deployment within a few requests instead of leaving the next request to
 * draw the same bad connection.
 */
class RecoverFromAbortedTransaction
{
    /**
     * Marks how many times the request has already been replayed so a
     * further 25P02 retries (up to MAX_ATTEMPTS) instead of looping forever.
     */
    protected const RETRIED_ATTRIBUTE = 'recovered_from_aborted_transaction';

    /**
     * The retry-safety argument in the class docblock applies to each
     * attempt, so the only bound needed is one that prevents a pathological
     * loop: three poisoned connections in a row is enough to report failure.
     */
    protected const MAX_ATTEMPTS = 3;

    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (QueryException $e) {
            if (! $this->isAbortedTransaction($e)) {
                throw $e;
            }

            $attempt = ((int) $request->attributes->get(self::RETRIED_ATTRIBUTE, 0)) + 1;

            // Context must be captured before rollbackAndDisconnect() zeroes
            // the transaction levels and drops the connections.
            $context = $this->logContext($request, $e, $attempt);

            // Heal on every path, including the give-up path: clearing the
            // aborted transaction here is what returns a clean connection to
            // the pool instead of re-poisoning the next request.
            $this->rollbackAndDisconnect();

            if ($attempt >= self::MAX_ATTEMPTS) {
                Log::error('Aborted transaction (25P02) persisted after recovery; giving up on request.', $context);

                throw $e;
            }

            Log::warning('Inherited aborted transaction (25P02); rolled back, reconnected, retrying request.', $context);

            $request->attributes->set(self::RETRIED_ATTRIBUTE, $attempt);

            return $next($request);
        }
    }

    protected function isAbortedTransaction(QueryException $e): bool
    {
        if ((string) $e->getCode() === '25P02') {
            return true;
        }

        return is_array($e->errorInfo ?? null) && ($e->errorInfo[0] ?? null) === '25P02';
    }

    /**
     * Structured context for the recovery log lines: enough to name the poison
     * source (host, open transaction levels, failing SQL) without recording
     * bound parameter values.
     *
     * @return array<string, mixed>
     */
    protected function logContext(Request $request, QueryException $e, int $attempt): array
    {
        $connections = [];

        foreach (DB::getConnections() as $connection) {
            $connections[$connection->getName()] = [
                'host' => $connection->getConfig('host'),
                'database' => $connection->getConfig('database'),
                'transaction_level' => $connection->transactionLevel(),
            ];
        }

        return [
            'attempt' => $attempt,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'connection' => $e->connectionName,
            'sqlstate' => (string) $e->getCode(),
            'sql' => $e->getSql(),
            'connections' => $connections,
        ];
    }

    protected function rollbackAndDisconnect(): void
    {
        foreach (DB::getConnections() as $connection) {
            try {
                // rollBack() decrements one level per call; loop until the
                // connection reports no open transaction. ROLLBACK itself
                // succeeds on an aborted transaction — that is what clears
                // the poisoned state before the connection goes back to the pool.
                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            } catch (Throwable) {
                // The connection may already be gone; disconnecting below
                // discards it regardless.
            }

            // The PHP transaction counter cannot see an inherited abort —
            // another client poisoned the pooled connection — so the loop
            // above skips it and disconnect() alone would hand the pool back
            // a connection that is still aborted server-side. A raw ROLLBACK
            // clears it; outside a transaction PostgreSQL answers with a
            // warning, never an error, so this is safe in every state.
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
