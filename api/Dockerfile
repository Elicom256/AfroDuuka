FROM dunglas/frankenphp:8.3-alpine AS frankenphp

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    opcache \
    exif

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

COPY . .

RUN chown -R frankenphp:frankenphp /app/storage /app/bootstrap/cache

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php artisan octane:status || exit 1

USER frankenphp

CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache && \
    php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000
