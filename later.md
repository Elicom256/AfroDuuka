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
