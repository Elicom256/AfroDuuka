#!/bin/sh
set -e

echo "Starting Laravel container..."

# Ensure directories exist (safe on every deploy)
mkdir -p storage/logs bootstrap/cache

if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ] || [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    # --no-autoloader prevents post-autoload-dump scripts (e.g. package:discover)
    # from firing before migrations have run. Autoloader is dumped manually later.
    composer install --no-interaction --no-scripts --no-autoloader
fi

# Clear any bad local cache files left on your host machine
rm -f bootstrap/cache/config.php bootstrap/cache/routes.php bootstrap/cache/services.php bootstrap/cache/packages.php
php artisan optimize:clear || true

echo "Running database migrations..."
# to remove the seeder in production
php artisan migrate --force

# Rebuild Laravel caches safely now that tables exist
echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching events..."
php artisan event:cache

echo "Caching views..."
php artisan view:cache

echo "Laravel ready"

# Hand off cleanly to container CMD (Octane)
exec "$@"
