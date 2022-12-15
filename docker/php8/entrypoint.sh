#!/bin/bash
export DEBIAN_FRONTEND=noninteractive
php -v
cd /var/testing && chmod +x ./runTests && ./runTests.sh