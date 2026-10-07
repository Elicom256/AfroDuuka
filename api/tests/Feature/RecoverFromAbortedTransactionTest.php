<?php

namespace Tests\Feature;

use App\Http\Middleware\RecoverFromAbortedTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Recovery only works if the heal can see the poison. An inherited aborted
 * transaction leaves PHP's transaction counter at zero — the state the
 * original fix could not detect — so no ROLLBACK was ever sent and the
 * pooled connection stayed aborted server-side for whoever drew it next.
 * These tests pin the raw ROLLBACK heal that closes that hole.
 */
class RecoverFromAbortedTransactionTest extends TestCase
{
    public function test_an_inherited_abort_is_invisible_to_the_php_counter_and_fails_the_next_query(): void
    {
        $this->poisonCurrentConnection();

        $this->assertSame(0, DB::connection()->transactionLevel());

        try {
            DB::select('select 1');
            $this->fail('The poisoned connection should have failed the next query with 25P02.');
        } catch (QueryException $e) {
            $this->assertSame('25P02', (string) $e->getCode());
        }

        $this->heal();
    }

    public function test_rollback_and_disconnect_heals_an_abort_the_php_counter_cannot_see(): void
    {
        $this->poisonCurrentConnection();

        $this->heal();

        $row = DB::select('select 1 as ok');

        $this->assertSame(1, (int) $row[0]->ok);
    }

    /**
     * Reproduce what a poisoned pooled connection looks like: the server is
     * inside an aborted transaction, but Laravel's counter never saw BEGIN
     * because another client opened it.
     */
    private function poisonCurrentConnection(): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->exec('BEGIN');

        try {
            $pdo->exec('SELECT 1/0');
            $this->fail('The probe statement should have failed.');
        } catch (\Throwable) {
            // Expected: this failure is what aborts the server-side transaction.
        }
    }

    private function heal(): void
    {
        $middleware = new RecoverFromAbortedTransaction();
        $heal = new ReflectionMethod($middleware, 'rollbackAndDisconnect');
        $heal->setAccessible(true);
        $heal->invoke($middleware);
    }
}
