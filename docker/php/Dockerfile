FROM php:8.4-fpm

WORKDIR /var/www

RUN apt-get update \
    && apt-get install -y \
        git \
        curl \
        unzip \
        libjpeg62-turbo-dev \
        libpng-dev \
        libsqlite3-dev \
        libwebp-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        gd \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["php-fpm"]
