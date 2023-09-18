#!/bin/bash
docker compose up -d logto-mariadb logto-mongodb &&
docker compose up php8 &&
docker compose down
