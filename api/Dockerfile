# ------------------------------------------------------------------------------
# Base image (FrankenPHP + PHP runtime)
# ------------------------------------------------------------------------------

FROM dunglas/frankenphp:1.2.0-php8.3-alpine AS base

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

# Copy dependencies from vendor stage
COPY --link --from=vendor /app/vendor ./vendor

# Copy application code
COPY --link . .

# Create and set permissions for Laravel writable directories using explicit IDs
RUN mkdir -p storage bootstrap/cache && \
    chown -R ${USER_ID}:${GROUP_ID} storage bootstrap/cache /app


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

CMD [ "php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000", "--watch" ]
