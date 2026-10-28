FROM composer:2 AS vendor

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


FROM node:22-bookworm-slim AS assets

WORKDIR /var/www

COPY package.json package-lock.json ./
RUN npm ci

COPY --from=vendor /var/www/vendor ./vendor
COPY . ./
RUN npm run build


FROM php:8.3-fpm-bookworm AS app

WORKDIR /var/www

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd mbstring opcache pcntl pdo_mysql zip \
    && apt-get purge -y --auto-remove \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
    && apt-get install -y --no-install-recommends \
        libfreetype6 \
        libjpeg62-turbo \
        libonig5 \
        libpng16-16 \
        libzip4 \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini-production /usr/local/etc/php/conf.d/99-production.ini
COPY --from=vendor /var/www/vendor ./vendor
COPY . ./
COPY --from=assets /var/www/public/build ./public/build

RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]


FROM nginx:1.27-alpine AS nginx

WORKDIR /var/www

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www /var/www

EXPOSE 80
