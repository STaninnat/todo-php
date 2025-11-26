FROM php:8.3-fpm-alpine3.22

# Upgrade all packages to fix vulnerabilities
RUN apk update && apk upgrade --no-cache \
    && apk add --no-cache mariadb-connector-c-dev bash git unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

COPY backend/composer.json backend/composer.lock ./

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY ./backend/src ./src
COPY ./backend/public ./public

WORKDIR /app