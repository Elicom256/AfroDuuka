#15 [vendor 4/4] RUN --mount=type=cache,id=composer,target=/tmp/cache     composer install         --no-interaction         --no-progress         --prefer-dist         --optimize-autoloader         --no-scripts
#15 0.345 Installing dependencies from lock file (including require-dev)
#15 0.351 Verifying lock file contents can be installed on current platform.
#15 0.472 Your lock file does not contain a compatible set of packages. Please run composer update.
#15 0.472 
#15 0.472   Problem 1
#15 0.472     - phpoffice/phpspreadsheet is locked to version 1.30.7 and an update of this package was not requested.
#15 0.472     - phpoffice/phpspreadsheet 1.30.7 requires ext-gd * -> it is missing from your system. Install or enable PHP's gd extension.
#15 0.472   Problem 2
#15 0.472     - maatwebsite/excel is locked to version 3.1.70 and an update of this package was not requested.
#15 0.472     - maatwebsite/excel 3.1.70 requires phpoffice/phpspreadsheet ^1.30.5 -> satisfiable by phpoffice/phpspreadsheet[1.30.7].
#15 0.472     - phpoffice/phpspreadsheet 1.30.7 requires ext-gd * -> it is missing from your system. Install or enable PHP's gd extension.
#15 0.472 
#15 0.472 To enable extensions, verify that they are enabled in your .ini files:
#15 0.472     - /usr/local/etc/php/php-cli.ini
#15 0.472     - /usr/local/etc/php/conf.d/docker-php-ext-bz2.ini
#15 0.472     - /usr/local/etc/php/conf.d/docker-php-ext-sodium.ini
#15 0.472     - /usr/local/etc/php/conf.d/docker-php-ext-zip.ini
#15 0.472 You can also run `php --ini` in a terminal to see which files are used by PHP in CLI mode.
#15 0.472 Alternatively, you can run Composer with `--ignore-platform-req=ext-gd` to temporarily ignore these required extensions.
#15 ERROR: process "/bin/sh -c composer install         --no-interaction         --no-progress         --prefer-dist         --optimize-autoloader         --no-scripts" did not complete successfully: exit code: 2

#13 [base 3/4] RUN install-php-extensions     pgsql     pdo_pgsql     redis     opcache     pcntl     bcmath     exif     gd     zip
#13 CANCELED
------
 > [vendor 4/4] RUN --mount=type=cache,id=composer,target=/tmp/cache     composer install         --no-interaction         --no-progress         --prefer-dist         --optimize-autoloader         --no-scripts:
0.472     - maatwebsite/excel 3.1.70 requires phpoffice/phpspreadsheet ^1.30.5 -> satisfiable by phpoffice/phpspreadsheet[1.30.7].
0.472     - phpoffice/phpspreadsheet 1.30.7 requires ext-gd * -> it is missing from your system. Install or enable PHP's gd extension.
0.472 
0.472 To enable extensions, verify that they are enabled in your .ini files:
0.472     - /usr/local/etc/php/php-cli.ini
0.472     - /usr/local/etc/php/conf.d/docker-php-ext-bz2.ini
0.472     - /usr/local/etc/php/conf.d/docker-php-ext-sodium.ini
0.472     - /usr/local/etc/php/conf.d/docker-php-ext-zip.ini
0.472 You can also run `php --ini` in a terminal to see which files are used by PHP in CLI mode.
0.472 Alternatively, you can run Composer with `--ignore-platform-req=ext-gd` to temporarily ignore these required extensions.
------
Dockerfile:59
--------------------
  58 |     # --mount=type=cache persists Composer's cache across builds
  59 | >>> RUN --mount=type=cache,id=composer,target=/tmp/cache \
  60 | >>>     composer install \
  61 | >>>         --no-interaction \
  62 | >>>         --no-progress \
  63 | >>>         --prefer-dist \
  64 | >>>         --optimize-autoloader \
  65 | >>>         --no-scripts
  66 |     
--------------------
error: failed to solve: process "/bin/sh -c composer install         --no-interaction         --no-progress         --prefer-dist         --optimize-autoloader         --no-scripts" did not complete successfully: exit code: 2

---

# Server: POS checkout (and sales/purchases) fail on Render with SQLSTATE[25P02] "current transaction is aborted"

## Symptom
- On production (`https://afroduuka-ui.onrender.com/dashboard/pos`), submitting a POS checkout errors with:
  `SQLSTATE[25P02]: In failed sql transaction: 7 ERROR: current transaction is aborted, commands ignored until end of transaction block`
  failing SQL: `select "name" from "roles" where "roles"."id" = 3 limit 1`
- Same failure on sales and purchases. Does NOT happen in dev.

## What the reported SQL is NOT
- The `roles` SELECT is the tenant-scope role-name lookup (`api/app/Support/Auth/RolePermissions.php:91`, `api/app/Support/Tenant/BusinessContext.php:142`). It runs as the FIRST statement inside `DB::transaction` in `PosService::checkout` (`api/app/Services/PosService.php:175`) because every tenant query resolves the role before building its scope.
- A 25P02 on that SELECT means the transaction was **already aborted before any statement inside ran** — the aborter is upstream, the roles SELECT is just the victim that gets blamed.

## Why dev works but Render fails
- Dev `.env` (Postgres, host `pgsql`): `DB_EMULATE_PREPARES=true`.
- Render env (Neon **pooler**, host `ep-square-forest-b1x7w7m3-pooler.c-5.eu-central-1.aws.neon.tech` — PgBouncer transaction mode): `DB_EMULATE_PREPARES` is **not set**, so PDO falls back to real server-side prepared statements.
- `api/config/database.php` already documents the risk: *"Neon's pooled endpoint (PgBouncer transaction mode) breaks ... when real prepared statements are used; emulating prepares avoids it."* The `options` array only enables `PDO::ATTR_EMULATE_PREPARES => true` when the env var is set.
- Native prepared statements + PgBouncer transaction pooling mismatch (named statements replayed/re-reset across pooled backends) is what leaves the pooled connection in a poisoned/aborted transaction state, producing 25P02 on the next request's first query.

## Fix
1. Set `DB_EMULATE_PREPARES=true` in the Render environment (dashboard → afroduuka-api env vars) — mirrors dev, matches the documented intent in config/database.php. Update `renderenv.md` to include it.
2. Better/durable (no dashboard step): change `api/config/database.php` pgsql `options` to default `PDO::ATTR_EMULATE_PREPARES => true` (or auto-detect `-pooler.` in `DB_URL`). Safe for dev, which already runs emulated.
3. Verify on prod: run a POS checkout and confirm the sale row, stock decrement, and `new_sale` notification all land; check that reads (dashboard pages) still work.
4. Optional hardening: log the first real query error inside the transaction (e.g. `pg_last_error()` on the connection) so if 25P02 ever recurs after the prepare fix, the underlying failing statement is not masked again.

## Files touched
- `api/config/database.php` (pgsql connection options)
- `renderenv.md` (document `DB_EMULATE_PREPARES=true`)
- Render dashboard env vars (manual step)

---

# BEFORE LAUNCH checklist (full-app audit, 2026-10-11)

Full-app audit across API security, API production ops, UI, and deployment/infra.
Status scoring: DONE (in repo, commit listed) / needs-deploy (in repo, code ready, Render redeploy pending) / SHORT (1-line searchable description) / decision (env or product choice needed).

## Immediate (code already fixed in this branch)
- [DONE] Neon/PgBouncer 25P02 on POS/sales/purchases — config/database.php now defaults pgsql to emulated prepares unless `DB_EMULATE_PREPARES=false`. Commit `ea22a44`. **needs-deploy** on Render.
- [DONE] Render compose build failure (phpspreadsheet needs ext-gd; bare composer image lacks it) — vendor stage now builds from the runtime base; verified locally. Commit `b64b57f`. **needs-deploy** on Render.
- [DONE] Procurement API slice: relative `/api/procurement` baseUrl + token read from a non-existent `auth` reducer → now uses `VITE_BASE_URL` + `localStorage.getItem('token')` like every other slice (would 502/bearer-less on prod).
- [DONE] UI nginx: security headers now sent on hashed assets (add_header inheritance bug) and index.html set `Cache-Control: no-store`.
- [DONE] UI Dockerfile: `npm install` → `npm ci`, and the build now FAILS if the `VITE_BASE_URL` build arg is missing (no more silently baking `http://localhost/api`).
- [DONE] `qrcode` moved devDependencies → dependencies (runtime import in ReceiptView).
- [DONE] API CORS locked down: published `config/cors.php`, origins = `CORS_ALLOWED_ORIGINS` env (default `https://afroduuka-ui.onrender.com`), credentials off.
- [DONE] `reports` apiResource was registered without an auth guard → `auth:sanctum` now explicit (401 instead of policy 403 for guests).
- [DONE] SNS signing-cert URL host check tightened to exactly `sns.<region>.amazonaws.com[.cn]` (was any `.amazonaws.com` suffix); unit test `tests/Unit/WhatsApp/SnsSignatureVerifierTest.php` added.
- [FIXED+REVERTED] Printer DELETE — already covered by the Central delete gate (BlockRestrictedRoleActions); added a comment documenting the reason instead.

## UI — CORRECTNESS / prod wiring
- [decision] `ui/.env` still points `VITE_BASE_URL=http://localhost/api` (its own file, not committed issue: `ui/dist` from an earlier local build contains 68 baked occurrences). Ensure every deploy passes `--build-arg VITE_BASE_URL=https://afroduuka-api.onrender.com/api`. The Dockerfile now enforces it.
- [SHORT] `ui/src/app/store/features/branch/receipts/receiptsQuery.ts:34` hardcoded `|| 'http://localhost/api'` fallback — replace with `import.meta.env.VITE_BASE_URL` (or drop the fallback).
- [SHORT] Audit any other hardcoded `/api` or `localhost` strings in `ui/src` (`grep -rn "http://localhost" ui/src`).

## API — SECURITY (needs decision or careful check)
- [decision] Sanctum token expiry: `api/config/sanctum.php` `expiration => null` (tokens never expire). No refresh flow exists in the SPA, so a short TTL forces re-login. Pick 7–30d; add 401 re-login handling in the SPA.
- [SHORT] `api/app/Http/Requests/UpdateUserRequest.php:36` — unscoped `role_id` could escalate privileges (e.g. a branch manager setting their own role to Executive). Scope to the caller's business + allowed set; add a test.
- [SHORT] PaymentGateway API exposes stored credentials via index/show (`api/routes/payment-gateways.php`). Mask secrets server-side (return `configured: true` only) unless the frontend needs real values.
- [SHORT] Default new-user password `'password'` — force change on first login or generate a random one via email.
- [decision] Login throttling is per-IP only (`ThrottleRequests`); acceptable pre-launch. If credential stuffing is a concern, add per-account throttling.
- [SHORT] `reports` prefix group uses `auth:sanctum` but no `role` gate — double-check PolicyViewAll covers every business role.

## API — PRODUCTION OPS
- [HIGH decision] **No queue worker on Render.** Nothing runs `queue:work`; local `docker-compose.yml` has a worker service but Render has none. `QUEUE_CONNECTION=database` + no worker ⇒ CheckNotificationsJob / WhatsApp / SES suppression jobs accumulate in `jobs` table silently. Options: (a) paid Render worker service, (b) cron endpoint/`queue:work --stop-when-empty`, (c) handle synchronously (`QUEUE_CONNECTION=sync`) if acceptable for low volume. Also add `render.yaml` for reproducible deploys.
- [HIGH decision] **Scheduler** (`routes/console.php`) does not run on Render — scheduled reports/notifications/backups won't fire. Use Render Cron or a supervisor process for `schedule:work`.
- [SHORT] `APP_DEBUG=true` and `APP_URL=http://localhost` in renderenv.md — flip to `false` / `https://afroduuka-ui.onrender.com` before launch.
- [SHORT] Logs go to ephemeral container filesystem (lost on Redeploy). Point `LOG_CHANNEL` at a persistent sink (Neon? no — use a host/Sentry/papertrail) or accept loss.
- [SHORT] `config/database.php` PgBouncer: consider auto-detecting `-pooler.` in `DB_URL` so `DB_EMULATE_PREPARES` cannot be accidentally unset again.
- [SHORT] Add DB-backed "warm" pool or keep `php artisan octane:reload` after deploy; verify connections don't pile up past Neon pool limit.
- [SHORT] Verify migrations run on release (entrypoint supports `SKIP_MIGRATIONS=true` sidecar) and `php artisan about` shows production mode.

## DATA / SEEDING
- [decision] `DatabaseSeeder` is dev-oriented; do NOT run it on prod. Create a guarded `ProductionSeeder` (or `--seed` for a specific class) that provisions only: default roles (BusinessService creates them on signup), PaymentMethod rows (PaymentMethodSeeder — NOT in DatabaseSeeder today), plan/currency reference data.
- [SHORT] Verify every export/excel path (phpspreadsheet) works on prod PHP (ext-gd now present in base image — verified fix).

## DEPLOY BUILD / LAUNCH PATH
- [SHORT] `ui/dist/` root-owned from an earlier `sudo` build — `npm run build` fails at `prepare-out-dir` until `sudo rm -rf ui/dist`. Do this once locally before the next build.
- [SHORT] Confirm Render UI Dockerfile build passes `--build-arg VITE_BASE_URL=https://afroduuka-api.onrender.com/api`.
- [SHORT] Manual smoke on prod after redeploy: login → POS checkout completes → dashboard loads → a scheduled-notifications trigger fires → a report PDF/export downloads → product image uploads (8M post size) → WhatsApp outbox flush.
- [SHORT] Custom domain + TLS: nginx only listens on :80 (no cert); terminate TLS at Render/Router level.

## Perf noticed (not blocking)
- [SHORT] `roles` name lookup runs inside every tenant query transaction start; memoised per user/role in BusinessContext already. Fine for launch; revisit only if dashboard latency shows up on the pooler.
