FROM php:8.1-fpm-alpine

RUN apk add --update \
    git \
    autoconf \
    libtool \
    bash \
    g++ \
    vim \
    make

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# @see https://hub.docker.com/_/php

ARG php_ini_dir="/usr/local/etc/php"

RUN mv "$php_ini_dir/php.ini-production" "$PHP_INI_DIR/php.ini"

# @see https://xdebug.org/docs/compat
# Xdebug 3.1

ARG xdebug_client_host="127.0.0.1"
ARG xdebug_client_port=9003

RUN echo $php_ini_dir

RUN apk update
RUN apk add --upgrade php81-pecl-xdebug \
    && echo "zend_extension=/usr/lib/php81/modules/xdebug.so" > $php_ini_dir/conf.d/99-xdebug.ini \
    && echo "xdebug.client_port=$xdebug_client_port" >> $php_ini_dir/conf.d/99-xdebug.ini \
    && echo "xdebug.client_host=$xdebug_client_host" >> $php_ini_dir/conf.d/99-xdebug.ini \
    && echo "xdebug.mode=debug,develop,coverage" >> $php_ini_dir/conf.d/99-xdebug.ini

WORKDIR /app
