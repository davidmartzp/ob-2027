# Congress API, local development: PHP 8.5 + Apache, like the hosting.
FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/congreso.ini

# The web root is public/ by default; APACHE_DOCUMENT_ROOT=/var/www/html simulates a hosting
# that cannot change it (the root .htaccess handles that case).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

WORKDIR /var/www/html
