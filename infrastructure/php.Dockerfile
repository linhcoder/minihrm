FROM composer:2 AS dependencies
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs

FROM php:8.3-fpm-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl mbstring mysqli bcmath zip gd opcache \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/hrm/backend
COPY backend/ ./
COPY --from=dependencies /app/vendor ./vendor
RUN mkdir -p writable/cache writable/logs writable/session writable/uploads writable/mail writable/privacy \
    && chown -R www-data:www-data writable && chmod -R 700 writable
COPY infrastructure/php.ini /usr/local/etc/php/conf.d/minihrm.ini
USER www-data
CMD ["php-fpm"]
