#!/bin/bash
echo "Building PHP8 image..." &&
cd  ./docker/php8 && docker build . -t  ghcr.io/danielme85/lltdb-testbench:latest
