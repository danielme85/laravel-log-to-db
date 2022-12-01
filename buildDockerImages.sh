#!/usr/bin/env bash
echo "Building PHP8 image..." &&
cd  ./docker/php8 && docker build . -t danielme/laravel-php8:latest
