FROM alpine:latest

LABEL Maintainer="Daniel Mellum <mellum@gmail.com>" \
      Description="A simple docker image used in phpunit testing Laravel apps."

ENV DOCKERIZE_VERSION v0.6.1

RUN apk --no-cache add php8 php8-common php8-fpm php8-zip php8-json php8-openssl php8-curl \
    php8-zlib php8-xml php8-phar php8-intl php8-dom php8-xmlreader php8-xmlwriter php8-ctype \
    php8-mbstring php8-gd php8-session php8-pdo php8-pdo_mysql php8-tokenizer php8-posix \
    php8-fileinfo php8-opcache php8-cli php8-pcntl php8-iconv php8-simplexml php8-mongodb \
    curl git openssl openssh-client mysql-client bash

RUN apk add php8-pecl-pcov --repository=http://dl-cdn.alpinelinux.org/alpine/edge/testing

RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

RUN apk --no-cache add

RUN ln -s /usr/bin/php8 /usr/bin/php

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
