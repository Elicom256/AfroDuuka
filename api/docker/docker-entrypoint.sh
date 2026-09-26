#!/bin/sh
set -e

echo "Starting Laravel container..."

# Ensure directories exist (safe on every deploy)
mkdir -p storage/logs bootstrap/cache

# Install Composer packages only when they are missing.
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    # --no-autoloader prevents post-autoload-dump scripts (e.g. package:discover)
    # from firing before migrations have run. Autoloader is dumped manually later.
    composer install --no-interaction --no-scripts
fi

php artisan storage:link || true

# Local development does not need cache rebuilds on every restart.
if [ "$APP_ENV" != "local" ] && [ "$APP_ENV" != "development" ]; then
    rm -f bootstrap/cache/config.php bootstrap/cache/routes.php bootstrap/cache/services.php bootstrap/cache/packages.php
    php artisan optimize:clear || true
fi

echo "Running database migrations..."
# to remove the seeder in production
# Sidecar containers (e.g. the queue worker) set SKIP_MIGRATIONS=true so that
# migrations are not run twice concurrently against the same database.
if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
    php artisan migrate --force
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
