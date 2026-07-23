#!/bin/bash
set -u

while true; do
    php /var/www/html/artisan schedule:run --no-interaction
    sleep 60
done
