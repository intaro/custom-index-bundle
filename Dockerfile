ARG PHP_IMAGE_TAG
FROM php:${PHP_IMAGE_TAG}-cli-alpine

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /opt/test
