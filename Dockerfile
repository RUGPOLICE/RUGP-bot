FROM composer:2.0 AS vendor

# Install Laravel Envoy
RUN composer global require "laravel/envoy"

# Set the base image for subsequent instructions
FROM php:8.2


# Update packages
RUN apt-get update

# Install PHP and composer dependencies
RUN apt-get install -qq git curl libmcrypt-dev libjpeg-dev libpng-dev libfreetype6-dev libbz2-dev libzip-dev libmagickwand-dev libmagickcore-dev

# Clear out the local repository of retrieved package files
RUN apt-get clean

RUN pecl install imagick

# Install needed extensions
# Here you can install any other extension that you need during the test and deployment process
RUN docker-php-ext-install pdo_mysql zip
RUN docker-php-ext-enable imagick

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
