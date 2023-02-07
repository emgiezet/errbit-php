#!/bin/bash

composer config -g http-basic.gitlab.com gitlab-ci-token ${GITLAB_TOKEN}
composer install \
        --working-dir /app \
        --prefer-dist

php-fpm
