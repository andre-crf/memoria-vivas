FROM php:8.4.27-fpm

WORKDIR /var/www

RUN apt-get update \
    && apt-get install -y \
        git \
        curl \
        unzip \
        libjpeg62-turbo-dev \
        libpng-dev \
        libmagickwand-dev \
        libsqlite3-dev \
        libwebp-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        gd \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && pecl install imagick-3.8.1 \
    && docker-php-ext-enable imagick \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

CMD ["php-fpm"]
