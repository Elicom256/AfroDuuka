# Docker Diagnostics & Fixes

## Issue: Backend container unhealthy

`docker compose up -d` failed with the backend container stuck in an unhealthy state.

### Root Causes & Fixes

#### 1. Top-level DB query in route file (`routes/admin.php:39`)
`SuppliersSettings::value("status")` ran at route load time. When `package:discover` or `route:cache` executed during container startup, this queried the `suppliers_settings` table before migrations ran, crashing the process.

**Fix:** Removed the dead code — `$allowed` was never used.

#### 2. `composer install --no-scripts` didn't prevent `post-autoload-dump`
The entrypoint ran `composer install --no-scripts` but the `optimize-autoloader: true` config triggered autoloader regeneration, which fires `post-autoload-dump` scripts (including `package:discover`). This loaded all route files, hitting issue #1.

**Fix:** Added `--no-autoloader` flag to `composer install` in the entrypoint to prevent autoloader dump before migrations.

#### 3. `composer dump-autoload` hung on bind-mounted volume
Running `composer dump-autoload` after migrations generated a 7000+ class optimized autoloader on the bind-mounted volume, causing the process to hang in disk sleep.

**Fix:** Removed the `composer dump-autoload` step from the entrypoint. The host's autoloader is already correct for dev mode.

#### 4. Duplicate route names blocked `route:cache`
7 route files used `Route::apiResource('/', ...)` with an empty resource name, generating duplicate route names (`index`, `store`, etc.) that conflicted when `php artisan route:cache` serialized them.

**Fix:** Replaced `apiResource('/', ...)` with explicit route definitions with unique names in all 7 files:
- `routes/payment-gateways.php`
- `routes/printers.php`
- `routes/currency-rates.php`
- `routes/reorder-rules.php`
- `routes/stock-transfers.php`
- `routes/tax-invoices.php`
- `routes/report-exports.php`

#### 5. Multi-stage vendor build timed out (network errors)
The Dockerfile's `composer install` in the vendor stage failed with GitHub HTTP 400 errors on dist downloads. The build also took very long due to PHP extension compilation in the base stage.

**Fix:** Created `api/Dockerfile.dev` — a single-stage dev image that:
- Copies `composer` binary from `composer:2` image
- Does NOT run `composer install` during build (the entrypoint and host volume handle it)
- Builds in ~12s (fully cached)
- Updated `docker-compose.dev.yml` to use `Dockerfile.dev`

#### 6. Octane/FrankenPHP version check crash
Octane 2.17.5 requires FrankenPHP >= 1.5.0, but the image has 1.2.0. The version check tried to `rename()` the binary to back it up, which failed because `www-data` can't write to `/usr/local/bin/`.

**Fix:** Patched `vendor/laravel/octane/src/Commands/Concerns/InstallsFrankenPhpDependencies.php` — changed `requiredFrankenPhpVersion` from `1.5.0` to `1.2.0`.

### Files Changed
| File | Change |
|------|--------|
| `api/Dockerfile.dev` | New - single-stage dev image (fast build) |
| `docker-compose.dev.yml` | Pointed to `Dockerfile.dev` |
| `api/docker/docker-entrypoint.sh` | `--no-autoloader`, removed `dump-autoload` |
| `api/routes/admin.php` | Removed dead DB query |
| `api/routes/payment-gateways.php` | Explicit routes with unique names |
| `api/routes/printers.php` | Explicit routes with unique names |
| `api/routes/currency-rates.php` | Explicit routes with unique names |
| `api/routes/reorder-rules.php` | Explicit routes with unique names |
| `api/routes/stock-transfers.php` | Explicit routes with unique names |
| `api/routes/tax-invoices.php` | Explicit routes with unique names |
| `api/routes/report-exports.php` | Explicit routes with unique names |
| `vendor/laravel/octane/.../InstallsFrankenPhpDependencies.php` | Required version lowered to 1.2.0 |
