# syntax=docker/dockerfile:1.7
# Production image: PHP-FPM 8.2 serving the Laravel app. Nginx runs as a separate container (see docker-compose.yml).

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Extensions (intl, gd, ...) are installed in the runtime stage, not in the composer image.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM php:8.2-fpm-alpine AS app
RUN apk add --no-cache icu-libs libzip libpng freetype libjpeg-turbo sqlite-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev freetype-dev libjpeg-turbo-dev sqlite-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" intl zip gd pdo_sqlite opcache bcmath \
    && apk del .build-deps
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /app /var/www/html
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app database/data /var/www/public-shared \
    && chown -R www-data:www-data storage bootstrap/cache database /var/www/public-shared

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint
USER www-data
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
