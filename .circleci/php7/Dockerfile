FROM alpine:latest

LABEL Maintainer="Daniel Mellum <mellum@gmail.com>" \
      Description="A simple docker image used in phpunit testing Laravel apps."

ENV DOCKERIZE_VERSION v0.6.1

RUN apk --no-cache add php7 php7-common php7-fpm php7-zip php7-json php7-openssl php7-curl \
    php7-zlib php7-xml php7-phar php7-intl php7-dom php7-xmlreader php7-xmlwriter php7-ctype \
    php7-mbstring php7-gd php7-session php7-pdo php7-pdo_mysql php7-tokenizer php7-posix \
    php7-fileinfo php7-opcache php7-cli php7-mcrypt php7-pcntl php7-iconv php7-simplexml php7-mongodb \
    curl git openssl openssh-client mysql-client bash

RUN apk add php7-pecl-pcov --repository=http://dl-cdn.alpinelinux.org/alpine/edge/testing

RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

RUN apk --no-cache add

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh


ENTRYPOINT ["entrypoint.sh"]
