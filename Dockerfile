FROM php:8.4-fpm-alpine

# Upgrade all Alpine packages to pick up latest security patches
RUN apk upgrade --no-cache

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
        git \
        unzip \
        icu-dev \
        libpq-dev \
        linux-headers \
    && docker-php-ext-install \
        intl \
        pdo_pgsql \
        opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies (layers cached until composer files change)
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy application source
COPY . .

# Finish Composer setup (autoloader + post-install scripts)
RUN composer dump-autoload --optimize \
    && composer run-script post-install-cmd --no-interaction

RUN chown -R www-data:www-data var/
