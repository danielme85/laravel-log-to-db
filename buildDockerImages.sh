#!/bin/bash
echo "Building PHP8 image..." &&
cd  ./docker/php8 && docker buildx build --platform linux/amd64,linux/arm64 --push . -t  ghcr.io/danielme85/lltdb-testbench:latest
