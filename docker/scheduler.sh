#!/bin/bash
set -u

while true; do
    php /var/www/html/artisan schedule:run --no-interaction >> /dev/stdout 2>&1
    sleep 60
done
