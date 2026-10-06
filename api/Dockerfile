# ------------------------------------------------------------------------------
# Base image (FrankenPHP + PHP runtime)
# ------------------------------------------------------------------------------

# FrankenPHP must be >= 1.5.0 for Octane 2.x. This was pinned at 1.2.0, which the
# dev image never noticed because its CMD is `artisan serve` and never loads Octane.
# The prod CMD is `octane:start --server=frankenphp`, and Octane's
# ensureFrankenPhpBinaryMeetsRequirements() compares the binary against its
# requiredFrankenPhpVersion. Below the floor it calls confirm(..., default: true),
# which in a container with no TTY answers *yes*, so it tries to swap the binary:
# rename(/usr/local/bin/frankenphp, ...) -> Permission denied, because the image
# drops to USER www-data while the binary is root-owned. The uncaught exception
# exits 1, restart: unless-stopped brings it straight back, and the container
# never reaches a healthy state — which also blocks the frontend, since it waits
# on `condition: service_healthy`.
FROM dunglas/frankenphp:1.9.1-php8.3-alpine AS base

# 1. Declare ARGs at the top of the base image
ARG USER_ID=1000
ARG GROUP_ID=1000

# 2. Modify the user right here on the Alpine runtime base layer
RUN apk add --no-cache shadow && \
    usermod -u ${USER_ID} www-data && \
    groupmod -g ${GROUP_ID} www-data

# Install PHP extensions using the official installer script
RUN install-php-extensions \
    pgsql \
    pdo_pgsql \
    redis \
    opcache \
    pcntl \
    bcmath \
    exif


# ------------------------------------------------------------------------------
# Dependency layer (Composer)
# ------------------------------------------------------------------------------

FROM composer:2 AS vendor

WORKDIR /app

# Only dependency files first for optimal layer caching
COPY composer.json composer.lock ./

# --mount=type=cache persists Composer's cache across builds
RUN --mount=type=cache,id=composer,target=/tmp/cache \
    composer install \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts


# ------------------------------------------------------------------------------
# Application runtime image
# ------------------------------------------------------------------------------

FROM base AS app

# 3. Re-declare ARGs so this stage can see them for directory chowning
ARG USER_ID=1000
ARG GROUP_ID=1000

WORKDIR /app

# FIXED: Copy the global composer executable from the vendor stage into system binaries
COPY --from=vendor /usr/bin/composer /usr/bin/composer

# Copy dependencies from vendor stage
COPY --link --from=vendor /app/vendor ./vendor

# Copy application code
COPY --link . .

# Create and set permissions for Laravel writable directories using explicit IDs
# storage/framework/{views,cache,sessions} and storage/logs are excluded by
# .dockerignore, and Laravel's default view config resolves view.compiled with
# realpath(storage_path('framework/views')). If that directory is absent the
# realpath() returns false, so `php artisan view:cache` throws "View path not
# found" and the entrypoint (set -e) crash-loops the container. Create them.
# Only the writable paths are chowned. This used to be `chown -R ... /app`,
# which walked the whole image (vendor included) on every cache miss and made a
# one-line change rebuild for minutes. public/ is included because the entrypoint
# runs `storage:link`, which writes the symlink there.
RUN mkdir -p \
        storage/framework/views \
        storage/framework/cache \
        storage/framework/sessions \
        storage/logs \
        bootstrap/cache && \
    chown -R ${USER_ID}:${GROUP_ID} storage bootstrap/cache public


# ------------------------------------------------------------------------------
# Healthcheck
# ------------------------------------------------------------------------------

HEALTHCHECK --interval=30s \
    --timeout=5s \
    --start-period=20s \
    --retries=3 \
    CMD wget -qO- http://127.0.0.1:8000/up || exit 1


# ------------------------------------------------------------------------------
# Entry point
# ------------------------------------------------------------------------------

COPY --chown=${USER_ID}:${GROUP_ID} --chmod=755 docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh


# ------------------------------------------------------------------------------
# Security
# ------------------------------------------------------------------------------

USER www-data

EXPOSE 8000


ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
# ------------------------------------------------------------------------------
# Runtime command (Octane + FrankenPHP)
# ------------------------------------------------------------------------------

# CMD [ "php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000", "--watch" ]
CMD [ "php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000" ]
