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
        su-exec \
    && docker-php-ext-install pdo pdo_mysql bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache autoconf g++ make

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
