# API del congreso: PHP 8.5 + Apache, como el hosting.
FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/congreso.ini

# Por defecto la raíz web es public/; con APACHE_DOCUMENT_ROOT=/var/www/html se prueba
# el despliegue en un hosting que no deja cambiarla (lo resuelve el .htaccess de la raíz).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

WORKDIR /var/www/html
