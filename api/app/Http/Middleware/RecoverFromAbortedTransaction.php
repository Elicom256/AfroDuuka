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
 * One-shot recovery from an inherited aborted transaction (SQLSTATE 25P02).
 *
 * A 25P02 that reaches this middleware can only mean the connection opened the
 * request already carrying an aborted transaction: a genuine failure inside the
 * request's own DB::transaction surfaces the original error, and nothing in the
 * checkout path swallows a failure and continues. The first statement of an
 * aborted transaction fails before anything this request wrote can commit, so
 * rolling back, dropping the connection and replaying the request once is safe
 * even for the non-idempotent POS checkout.
 */
class RecoverFromAbortedTransaction
{
    /**
     * Marks the request as already replayed so a second 25P02 rethrows
     * instead of looping.
     */
    protected const RETRIED_ATTRIBUTE = 'recovered_from_aborted_transaction';

    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (QueryException $e) {
            if (! $this->isAbortedTransaction($e)) {
                throw $e;
            }

            $attempt = $request->attributes->has(self::RETRIED_ATTRIBUTE) ? 2 : 1;

            // Context must be captured before rollbackAndDisconnect() zeroes
            // the transaction levels and drops the connections.
            $context = $this->logContext($request, $e, $attempt);

            if ($attempt === 2) {
                Log::error('Aborted transaction (25P02) persisted after recovery; giving up on request.', $context);

                throw $e;
            }

            $this->rollbackAndDisconnect();

            Log::warning('Inherited aborted transaction (25P02); rolled back, reconnected, retrying request once.', $context);

            $request->attributes->set(self::RETRIED_ATTRIBUTE, true);

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

            $connection->disconnect();
        }
    }
}
