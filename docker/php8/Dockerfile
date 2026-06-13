FROM php:8.4-cli-alpine

LABEL Maintainer="Daniel Mellum <mellum@gmail.com>" \
      Description="Lightweight PHP CLI image for phpunit testing."

RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    autoconf \
    gcc \
    g++ \
    make \
    linux-headers \
    mysql-client \
    libzip-dev \
    oniguruma-dev

RUN docker-php-ext-install pdo_mysql mbstring zip pcntl

RUN pecl install mongodb pcov \
    && docker-php-ext-enable mongodb pcov

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.2.1/wait /wait
RUN chmod +x /wait
