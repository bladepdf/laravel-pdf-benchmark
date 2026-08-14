FROM node:22.16.0-bookworm-slim@sha256:048ed02c5fd52e86fda6fbd2f6a76cf0d4492fd6c6fee9e2c463ed5108da0e34 AS node
FROM composer:2.10.2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040 AS composer
FROM php:8.4.1-cli-bookworm@sha256:246d2ca7e9cf21c4e7c354ce87e87bf1ad6d41b2061c77668845e04a8f8889d4

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates curl git jq libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
        libicu-dev libzip-dev poppler-utils procps qpdf unzip zip \
        libasound2 libatk-bridge2.0-0 libatk1.0-0 libcups2 libdbus-1-3 libdrm2 \
        libgbm1 libgtk-3-0 libnspr4 libnss3 libx11-xcb1 libxcomposite1 \
        libxdamage1 libxfixes3 libxkbcommon0 libxrandr2 xdg-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) bcmath gd intl pcntl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=node /usr/local/ /usr/local/
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN git config --system --add safe.directory /workspace

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PUPPETEER_CACHE_DIR=/opt/puppeteer

WORKDIR /workspace

COPY package.json package-lock.json ./
RUN npm ci && node node_modules/puppeteer/install.mjs

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN mkdir -p bootstrap/cache storage/framework/benchmark storage/framework/cache/data storage/framework/sessions storage/framework/views \
    && chmod -R a+rw storage bootstrap/cache \
    && composer install --no-interaction --prefer-dist --optimize-autoloader \
    && npm run assets \
    && npm run build \
    && chmod -R a+rw storage bootstrap/cache

CMD ["php", "artisan", "list"]
