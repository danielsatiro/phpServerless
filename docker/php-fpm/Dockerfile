FROM alpine:3.20

RUN apk add --update \
    rabbitmq-c-dev \
    php82-fpm \
    php82-apcu \
    php82-ctype \
    php82-curl \
    php82-dom \
    php82-gd \
    php82-fileinfo \
    php82-iconv \
    php82-json \
    php82-intl \
    php82-mbstring \
    php82-opcache \
    php82-openssl \
    php82-pdo \
    php82-pdo_mysql \
    php82-pdo_sqlite \
    php82-mysqli \
    php82-xml \
    php82-zlib \
    php82-phar \
    php82-tokenizer \
    php82-session \
    php82-simplexml \
    php82-sqlite3 \
    php82-xdebug \
    php82-xmlreader \
    php82-xmlwriter \
    php82-zip \
    php82-redis \
    php82-bcmath \
    php82-imap \
    php82-sodium \
    php82-exif \
    make \
    curl \
    git \
    memcached \
    nodejs \
    npm

# Criar link simbólico para o binário php
RUN ln -s /usr/bin/php82 /usr/bin/php

RUN apk add --no-cache bash

RUN rm -rf /var/cache/apk/* && rm -rf /tmp/* && \
    curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

#ADD xdebug.ini  /etc/php/conf.d/

ADD api.pool.conf /etc/php82/php-fpm.d/

CMD ["php-fpm82", "-F"]

WORKDIR /var/www/api
EXPOSE 9000
