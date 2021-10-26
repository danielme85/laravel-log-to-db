#!/usr/bin/env bash
echo "Building PHP7 image..." &&
cd .circleci/php7 && docker build . -t danielme/laravel-circleci-php7:latest &&
echo "Building PHP8 image..." &&
cd  ../../.circleci/php8 && docker build . -t danielme/laravel-circleci-php8:latest
