#!/usr/bin/env bash
docker-compose up -d mariadb mongo &&
docker-compose up php7 &&
docker-compose up php8 &&
docker-compose down
