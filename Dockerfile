FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/public

RUN docker-php-ext-install mysqli \
    && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf

WORKDIR /var/www
