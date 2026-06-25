# Docker Build Error — Root Cause

## Error

The Docker build failed with two symptoms:
1. **CANCELED** `[base 2/2] RUN install-php-extensions` (44.6s)
2. **ERROR** `[vendor 4/4] RUN composer install`

## Root Causes

### 1. Base layer cancellation
`install-php-extensions` compiles PHP extensions from source, which is slow (44.6s) and was likely cancelled due to a build timeout or manual interruption. This is inherent to the tool — it recompiles the static FrankenPHP binary for each extension.

### 2. Composer install failure
Two issues combined:

- **`--classmap-authoritative`** forces Composer to generate a classmap-only autoloader and fail if any class cannot be resolved. This flag is unnecessary when `--optimize-autoloader` is already used.
- **`post-autoload-dump` script** runs `php artisan package:discover` during `composer install`. At build time there is no `.env` file, so the artisan command fails, cascading into a build error.

## Fixes Applied

| Issue | Fix |
|---|---|
| `install-php-extensions` slow (no fix needed) | Kept as-is — it's the correct approach for FrankenPHP's static build |
| Composer `--classmap-authoritative` | Removed — redundant with `--optimize-autoloader` |
| `post-autoload-dump` fails at build time | Added `--no-scripts` to skip build-time scripts; caches are regenerated at runtime in the entrypoint |
| Entrypoint typo `php atisan` → `php artisan` | Fixed |
| No layer-cache for Composer | Added `--mount=type=cache,id=composer` |
| No layer-cache for npm (UI) | Added `--mount=type=cache,id=npm` |
| Cache invalidation on COPY | Switched to `COPY --link` (hard-link, parent-layer changes don't invalidate) |
| Missing image metadata | Added `LABEL org.opencontainers.image.*` |
