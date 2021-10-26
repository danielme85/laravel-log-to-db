#!/bin/bash
export DEBIAN_FRONTEND=noninteractive
bash <(cat /etc/os-release; echo 'echo ${VERSION/*, /}')
php -v
echo "Entrypoint/Boot actions completed..."
