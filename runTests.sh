#!/bin/bash
composer install --no-interaction
dockerize -wait tcp://mariadb:3306 -timeout 1m &&
./vendor/bin/phpunit --coverage-clover coverage.xml &&
curl -Os https://uploader.codecov.io/latest/linux/codecov &&
chmod +x codecov &&
./codecov