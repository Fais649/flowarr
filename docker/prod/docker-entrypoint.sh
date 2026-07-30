#!/bin/bash

set -e

PUID=${PUID:-1000}
PGID=${PGID:-1000}

groupmod -o -g $PGID www-data
usermod -o -u $PUID www-data
addgroup www-data video || true
addgroup www-data render || true
chown -R www-data:www-data storage bootstrap/cache

APP_DIR=/var/www/html
cd "$APP_DIR"
# Create .env from example if it doesn't exist
 if [ ! -f .env.prod ]; then
     cp .env.prod.example .env.prod
     echo "ERROR: .env.prod not found. Created from
 template."
     echo "Please configure .env.prod and restart."
     exit 1
 fi

 cp .env.prod .env

# Clear and rebuild config cache (env values changed)
php artisan config:clear
php artisan route:clear
php artisan event:clear
php artisan view:clear

# Run migrations if requested
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

# Re-cache config/events/routes/views
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

echo "Starting PHP-FPM..."
php-fpm -D

for i in $(seq 1 10); do
    if [ -f /usr/local/var/run/php-fpm.pid ] || pgrep -f 'php-fpm.*master' > /dev/null 2>&1; then
        break
    fi
    sleep 1
done

if ! pgrep -f 'php-fpm.*master' > /dev/null 2>&1 && [ ! -f /usr/local/var/run/php-fpm.pid ]; then
    echo "ERROR: PHP-FPM failed to start"
    exit 1
fi

# Start dynamic worker supervisor (watches DB, spawns/kills per UI-defined worker)
echo "Starting worker supervisor..."
worker-supervisor.sh &
SUPERVISOR_PID=$!
echo "Worker supervisor started (PID $SUPERVISOR_PID)"

echo "Starting nginx..."

# Trap SIGTERM and SIGINT for graceful shutdown
trap 'echo "Shutting down..."; nginx -s quit; kill $SUPERVISOR_PID 2>/dev/null; kill $(pgrep -f "php-fpm" | grep -v "^$" | tr "\n" " ") 2>/dev/null; exit 0' SIGTERM SIGINT

# Start nginx in foreground
nginx -g "daemon off;"
