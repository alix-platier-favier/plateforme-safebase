FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    libzip-dev \
    unzip \
    icu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl zip pdo_mysql mysqli pdo

WORKDIR /var/www

COPY . .

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
