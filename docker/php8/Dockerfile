FROM ubuntu:jammy

LABEL Maintainer="Daniel Mellum <mellum@gmail.com>" \
      Description="A simple docker image used in phpunit testing Laravel apps."

ENV DOCKERIZE_VERSION v0.7.0
ENV TZ=America/New_York
ARG DEBIAN_FRONTEND=noninteractive

WORKDIR /

RUN apt update && apt install -y lsb-release gnupg2 ca-certificates apt-transport-https software-properties-common
RUN apt update && add-apt-repository ppa:ondrej/php
RUN apt update && apt upgrade -y
RUN apt install -y curl git openssl openssh-client mysql-client bash libzip-dev zip wget
RUN apt install -y php8.3 php8.3-dev php8.3-mysql php8.3-mongodb php8.3-curl php8.3-mbstring php8.3-pcov php8.3-cli

RUN pecl install pcov
RUN pecl install mongodb

#RUN mkdir -p /etc/php81/mods-available && echo "extension=mongodb.so" >> /etc/php81/mods-available/mongodb.ini

RUN apt install wget -y

RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.2.1/wait /wait
RUN chmod +x /wait
