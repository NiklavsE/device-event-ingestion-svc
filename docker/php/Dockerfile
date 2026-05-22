FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
        bash \
        git \
        unzip \
        autoconf \
        g++ \
        make \
        mysql-client \
        oniguruma-dev \
        icu-dev \
    && docker-php-ext-install pdo pdo_mysql bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache autoconf g++ make

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
