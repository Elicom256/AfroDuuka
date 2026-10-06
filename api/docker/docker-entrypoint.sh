#!/bin/sh
set -e

echo "Starting Laravel container..."

# Ensure directories exist (safe on every deploy). storage/framework/views must
# exist before view:cache/config:cache: view.compiled is realpath()'d and a
# missing directory makes it false, which aborts this script under set -e.
mkdir -p storage/logs storage/framework/views storage/framework/cache storage/framework/sessions bootstrap/cache

# Install Composer packages only when they are missing.
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    # --no-autoloader prevents post-autoload-dump scripts (e.g. package:discover)
    # from firing before migrations have run. Autoloader is dumped manually later.
    composer install --no-interaction --no-scripts
fi

# `storage:link` exits non-zero once the symlink exists, so calling it
# unconditionally printed "The [public/storage] link already exists." as an ERROR
# on every restart. Only run it when the link is actually missing.
if [ ! -e public/storage ]; then
    php artisan storage:link || true
fi

echo "Running database migrations..."
# to remove the seeder in production
# Sidecar containers (e.g. the queue worker) set SKIP_MIGRATIONS=true so that
# migrations are not run twice concurrently against the same database.
if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
    php artisan migrate --force
fi

# Local development does not need cache rebuilds on every restart.
if [ "$APP_ENV" != "local" ] && [ "$APP_ENV" != "development" ]; then
    rm -f bootstrap/cache/config.php bootstrap/cache/routes.php bootstrap/cache/services.php bootstrap/cache/packages.php
    php artisan optimize:clear || true
fi

if [ "$APP_ENV" != "local" ] && [ "$APP_ENV" != "development" ]; then
    echo "Caching config..."
    php artisan config:cache

    echo "Caching routes..."
    php artisan route:cache

    echo "Caching events..."
    php artisan event:cache

    echo "Caching views..."
    php artisan view:cache
fi

echo "Laravel ready"

# Hand off cleanly to the configured container command.
exec "$@"
