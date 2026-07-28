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
    case "$RAW_KEY" in
        base64:*) PREFIXED_KEY="$RAW_KEY" ;;
        *) PREFIXED_KEY="base64:${RAW_KEY}" ;;
    esac
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

# Start queue workers — one per UI-defined worker in the database
echo "Starting queue workers..."
QUEUE_PIDS=""
start_worker() {
    local name="$1" queue="$2" max_jobs="$3"
    while true; do
        php artisan queue:work --queue="$queue" --sleep=3 --tries=3 --max-time=3600 --max-jobs="$max_jobs"
        echo "Worker [$name] ($queue) exited, restarting in 2s..."
        sleep 2
    done
}

# Collect worker configs from DB, fallback to per-queue defaults
# Each line: queue|name|max_jobs
php artisan tinker --execute '
$workers = \App\Models\Worker::all();
if ($workers->isEmpty()) {
    echo "transcode|Transcode|10\nsubtitle|Subtitle|10\n";
} else {
    foreach ($workers as $w) {
        $queue = match ((string) $w->job_type) {
            "transcode_media" => "transcode",
            default => "subtitle",
        };
        $safeName = str_replace([" ", "\"", "'", "|"], "_", $w->name);
        echo "{$queue}|{$safeName}|{$w->concurrency}\n";
    }
}
' 2>/dev/null > /tmp/worker-config.txt

# Start a queue:work process per worker
while IFS='|' read -r queue name max_jobs; do
    [ -z "$queue" ] && continue
    start_worker "$name" "$queue" "$max_jobs" &
    WPID=$!
    QUEUE_PIDS="$QUEUE_PIDS $WPID"
done < /tmp/worker-config.txt

echo "Started $(echo $QUEUE_PIDS | wc -w) queue worker(s)"
echo "Starting nginx..."

# Trap SIGTERM and SIGINT for graceful shutdown
trap 'echo "Shutting down..."; nginx -s quit; kill $QUEUE_PIDS 2>/dev/null; kill $(pgrep -f "php-fpm" | grep -v "^$" | tr "\n" " ") 2>/dev/null; exit 0' SIGTERM SIGINT

# Start nginx in foreground
nginx -g "daemon off;"
