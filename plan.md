# Fix: POS checkout `SQLSTATE[25P02] In failed sql transaction` (roles SELECT)

## Root cause (investigated)

`25P02` means PostgreSQL refuses every further statement because a statement **earlier in the
same transaction** already failed and was never rolled back. The reported query
(`select "name" from "roles" where "roles"."id" = 3 limit 1`, from
`BusinessContext::roleNameFor` / `RolePermissions::roleName`) is only the **first victim**
after the transaction was already aborted — not the culprit.

Evidence the poison arrives from **outside** the request:

- `laravel.log`: the same `25P02` hits the **rate limiter** (`update "cache"`) at the very
  start of requests → transactions arrive already aborted across requests.
- `laravel.log` (2026-10-06 22:35): a **fresh `php artisan migrate` process** fails twice on
  `alter table "cache" add primary key` — a brand-new process cannot inherit app state, so
  the dirty connection comes from **Neon's PgBouncer pool**.
- Exhaustive audit found **no swallowed exception** anywhere in the checkout path
  (`PosService::checkout`, listeners, sync queue, observers) — app code cannot abort and
  continue a transaction itself.
- Structural amplifiers: Octane never disconnects DB between requests
  (`DisconnectFromDatabases` exists in the vendor but is never wired), and
  cache + session + queue + rate limiter all open transactions on the **same pooled pgsql
  connection** (`CACHE_STORE=database`). The failed `cache` PK migration re-runs on every
  container boot under `set -e`, so one dirty pool conn crashes the deploy.

## Chunks (push after each, mark `- [x]` when done)

- [x] **Chunk 0** — This plan (`plan.md`), pushed.
- [x] **Chunk 1** — Wire Octane DB reset between requests: new `api/config/octane.php`
      enabling a new `App\Octane\ResetDatabaseState` listener on `OperationTerminated`
      (rolls back any leaked open transaction, then disconnects every PDO). Kills the
      "poisoned transaction carried into the next request" vector.
- [x] **Chunk 2** — Move cache off the database: `CACHE_STORE=file` in `.env.render`,
      `.env`, `.env.example` (+ `config/cache.php` default). Removes the per-request
      rate-limiter `BEGIN` against the pool. **Manual step: update/remove `CACHE_STORE`
      in the Render dashboard env vars too.**
- [x] **Chunk 3** — Make boot resilient: rewrite `0001_01_01_000001_create_cache_table.php`
      to be idempotent (guard `hasTable`, add missing PK with dedupe, same for
      `cache_locks`) and add a 3-attempt retry around `php artisan migrate --force` in
      `docker-entrypoint.sh` so one transient 25P02 cannot crash the deploy. Verified
      locally against a scratch Postgres: fresh-create, repair, and no-op paths pass;
      retry loop behaviour checked under `set -e`.
- [ ] **Chunk 4** — One-shot request recovery: new
      `App\Http\Middleware\RecoverFromAbortedTransaction`, **prepended** to the `api`
      group. On `QueryException` with SQLSTATE `25P02` (an inherited abort — proven
      safe to retry because nothing this request wrote can have committed): roll back,
      disconnect, retry the request exactly once; rethrow if it fails again.
- [ ] **Chunk 5** — First-failure observability: structured `Log::warning/error` from the
      recovery middleware (path, SQL, `transactionLevel`, connection host, attempt count)
      so the next incident names the poison source instead of only its victim.
- [ ] **Chunk 6** — Replace hard-coded `'payment_status_id' => 1` in
      `PosService::checkout` with a lookup of the sale's actual payment method on the
      business's `payment_methods` (fallback `cash`, then `null` — column is nullable).
- [ ] **Chunk 7** — Verify: `php -l` on touched files, run the test suite / lint as
      available, final push.

## Retry-safety argument (Chunk 4)

A `25P02` exception reaching the request edge can only mean the connection **inherited**
an already-aborted transaction: any real failure inside this request's own
`DB::transaction` surfaces the *original* error (Laravel rolls back and rethrows it), and
the audit above found no path that swallows a failure and continues. An inherited abort
fails the very first statement, so nothing this request executed has committed — rolling
back and replaying once is safe even for the non-idempotent POS checkout.
