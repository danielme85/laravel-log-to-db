FROM ubuntu:jammy

LABEL Maintainer="Daniel Mellum <mellum@gmail.com>" \
      Description="A simple docker image used in phpunit testing Laravel apps."

ENV DOCKERIZE_VERSION v0.6.1
ENV TZ=America/New_York
ARG DEBIAN_FRONTEND=noninteractive

WORKDIR /

RUN apt update && apt upgrade -y
RUN apt install -y curl git openssl openssh-client mysql-client bash libzip-dev zip wget \
    php8.1 php8.1-dev php8.1-mysql php8.1-mongodb php8.1-curl php8.1-mbstring php8.1-pcov php8.1-cli

RUN pecl install pcov
RUN pecl install mongodb

#RUN mkdir -p /etc/php81/mods-available && echo "extension=mongodb.so" >> /etc/php81/mods-available/mongodb.ini

RUN apt install wget -y

RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]