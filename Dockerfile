FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libsqlite3-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    APP_ENV=production \
    APP_DEBUG=0 \
    APP_TIMEZONE=Asia/Dhaka \
    MARKETPLACE_DATA_DIR=/var/data/db \
    MARKETPLACE_STORAGE_DIR=/var/data/storage

WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/data/db /var/data/storage \
    && chown -R www-data:www-data /var/data

EXPOSE 80
