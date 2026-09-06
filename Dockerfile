# CybCademy — Application Container
#
# Multi-stage build: Composer dependencies resolved in a build stage,
# copied into a lean runtime image - the runtime image never contains
# Composer itself or dev dependencies, keeping the production image
# smaller and reducing attack surface (Phase 7 Section 2, A06).
#
# Base: PHP-FPM, not Apache+mod_php - matches the confirmed Phase 4
# Section 11 decision (Nginx + PHP-FPM in Docker) over the alternatives
# considered during the architecture sanity-check at the start of this
# project.

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --no-scripts and --no-autoloader deferred to the final `composer install`
# after the full source tree is copied, so package scripts that touch
# application code (if any) run against the complete app, not a partial
# copy - --no-dev excludes PHPUnit etc. from the production image.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

FROM php:8.3-fpm-alpine AS runtime

# Extensions required by Laravel + the PostgreSQL/Redis stack confirmed
# in Phase 4: pdo_pgsql (not pdo_mysql - PostgreSQL is the confirmed DB),
# redis (via PECL), gd (image handling), zip (archive handling for
# course/policy uploads), opcache (production performance).
RUN apk add --no-cache postgresql-dev libzip-dev libpng-dev icu-dev \
    && docker-php-ext-install pdo_pgsql zip gd intl opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && apk del --no-cache postgresql-dev libzip-dev libpng-dev icu-dev

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Opcache tuned for production - validate_timestamps=0 means a container
# restart (part of the deployment process, per Phase 4 Section 11) is
# required to pick up code changes, which is the correct trade-off for an
# immutable-container deployment model rather than editing files in place.
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
