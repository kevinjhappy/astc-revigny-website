FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git unzip icu-dev libzip-dev oniguruma-dev \
    mysql-client autoconf g++ make linux-headers

RUN docker-php-ext-install intl pdo pdo_mysql opcache zip \
 && pecl install apcu && docker-php-ext-enable apcu

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app
