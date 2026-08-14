FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/public

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libcurl4-openssl-dev unzip \
    && docker-php-ext-install mysqli pcntl zip curl \
    && rm -rf /var/lib/apt/lists/* \
    && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf

WORKDIR /var/www
