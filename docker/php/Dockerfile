FROM php:8.4-fpm

WORKDIR /var/www

RUN apt-get update \
    && apt-get install -y \
        git \
        curl \
        unzip \
        libzip-dev \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["php-fpm"]