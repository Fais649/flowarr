#!/bin/bash

set -e

PUID=${PUID:-1000}
PGID=${PGID:-1000}

groupmod -o -g $PGID www-data
usermod -o -u $PUID www-data
addgroup www-data video || true
addgroup www-data render || true
chown -R www-data:www-data storage bootstrap/cache
chown www-data:www-data /media 2>/dev/null || true

APP_DIR=/var/www/html
cd "$APP_DIR"

if [ ! -f .env.prod ]; then
     cp .env.prod.example .env.prod
     echo "ERROR: .env.prod not found. Created from template."
     echo "Please configure .env.prod and restart."
     exit 1
fi

cp .env.prod .env

php artisan config:clear
php artisan route:clear
php artisan event:clear
php artisan view:clear

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

exec /usr/bin/supervisord -n -c /etc/supervisord.conf
