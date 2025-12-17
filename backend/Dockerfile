FROM php:8.3-fpm-alpine3.22 AS base

# Install system dependencies
RUN apk update && apk upgrade --no-cache \
    && apk add --no-cache mariadb-connector-c-dev bash git unzip linux-headers \
    && docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# --- Development Stage ---
FROM base AS dev
# Install XDebug
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# Install dependencies including dev
RUN composer install --prefer-dist --no-interaction

# --- Production Stage ---
FROM base AS prod

# Install production dependencies only
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Copy application source
COPY src ./src
COPY public ./public
COPY phinx.php ./phinx.php
COPY db ./db

# Set permissions
RUN chown -R www-data:www-data /app
