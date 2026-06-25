#!/bin/sh
set -e

echo "Starting Laravel container..."

# Ensure directories exist (safe on every deploy)
mkdir -p storage/logs bootstrap/cache

# 1. FIXED FOR DEV: Install composer dependencies before doing anything else
if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ] || [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction
fi

# Clear stale caches from previous deployments
php artisan optimize:clear || true

# 2. FIXED FOR BOOTSTRAPPING: Run migrations BEFORE caching 
# This prevents route/config cache tasks from crashing on missing tables
echo "Running database migrations..."
php artisan migrate --force

# Rebuild Laravel caches
echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching events..."
php artisan event:cache

echo "Caching views..."
php artisan view:cache

echo "Laravel ready"

# Hand off to container CMD (Octane)
exec "$@"
