#!/bin/bash
set -e

APP_DIR=/var/www/html
cd "$APP_DIR"

# Create .env from example if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example.docker .env
fi

# Handle APP_KEY — write to .env, NOT append
if [ -z "${APP_KEY:-}" ] || [ "${APP_KEY}" = "base64:" ]; then
    echo "No APP_KEY provided — generating one..."
    php artisan key:generate --force
    echo "APP_KEY generated. WARNING: Sessions will be lost on restart unless you set APP_KEY in your .env file."
else
    RAW_KEY="${APP_KEY}"
    unset APP_KEY
    # Build the prefixed key
    case "$RAW_KEY" in
        base64:*) PREFIXED_KEY="$RAW_KEY" ;;
        *) PREFIXED_KEY="base64:${RAW_KEY}" ;;
    esac
    # Replace APP_KEY line in .env (remove any existing, add new)
    sed -i '/^APP_KEY=/d' .env
    echo "APP_KEY=${PREFIXED_KEY}" >> .env
fi

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

# Start PHP-FPM in background
php-fpm -D

# Verify PHP-FPM started (wait for PID file)
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

echo "Starting nginx..."

# Trap SIGTERM and SIGINT for graceful shutdown
trap 'echo "Shutting down..."; nginx -s quit; kill $(pgrep -f "php-fpm" | grep -v "^$" | tr "\n" " ") 2>/dev/null; exit 0' SIGTERM SIGINT

# Start nginx in foreground
nginx -g "daemon off;"
