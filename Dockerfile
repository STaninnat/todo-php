FROM php:8.2-fpm-alpine3.22

# Upgrade all packages to fix vulnerabilities
RUN apk update && apk upgrade --no-cache \
    && apk add --no-cache mariadb-connector-c-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && apk add --no-cache bash

WORKDIR /app

COPY ./src ./src
COPY ./public ./public
COPY ./vendor ./vendor

WORKDIR /app/public