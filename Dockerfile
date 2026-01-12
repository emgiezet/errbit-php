FROM php:8.3-fpm-alpine

RUN apk add --update \
    git \
    autoconf \
    libtool \
    bash \
    g++ \
    vim \
    make \
    linux-headers

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# @see https://hub.docker.com/_/php

ARG php_ini_dir="/usr/local/etc/php"

RUN mv "$php_ini_dir/php.ini-production" "$PHP_INI_DIR/php.ini"

# @see https://xdebug.org/docs/compat
# Xdebug 3.3+ for PHP 8.3

ARG xdebug_client_host="127.0.0.1"
ARG xdebug_client_port=9003

RUN echo $php_ini_dir

# Install Xdebug via PECL for PHP 8.3
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.client_port=$xdebug_client_port" >> $php_ini_dir/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=$xdebug_client_host" >> $php_ini_dir/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=debug,develop,coverage" >> $php_ini_dir/conf.d/docker-php-ext-xdebug.ini

WORKDIR /app
