FROM php:8-fpm

RUN apt-get update \
    && apt-get install -y libpq-dev curl cron \
    && docker-php-ext-install pdo_pgsql

# Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

