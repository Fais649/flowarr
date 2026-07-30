#!/bin/bash
# Dynamic worker supervisor — watches the workers table and spawns/terminates
# queue:work processes as workers are added/removed in the UI.
# Runs as a background process inside the container.

set -e

WORKER_PIDS_FILE="/tmp/worker-supervisor-pids.txt"
INTERVAL=15  # check every 15 seconds

# Kill a worker by its DB row ID
kill_worker() {
    local wid="$1"
    local pid
    pid=$(grep "^${wid}|" "$WORKER_PIDS_FILE" 2>/dev/null | cut -d'|' -f2)
    if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
        kill "$pid" 2>/dev/null
        wait "$pid" 2>/dev/null || true
        echo "supervisor: stopped worker id=$wid"
    fi
    grep -v "^${wid}|" "$WORKER_PIDS_FILE" > "${WORKER_PIDS_FILE}.tmp" 2>/dev/null
    mv "${WORKER_PIDS_FILE}.tmp" "$WORKER_PIDS_FILE" 2>/dev/null || true
}

# Start a queue worker for a DB worker row
start_worker() {
    local wid="$1" name="$2" queue="$3" max_jobs="$4"
    local pid

    # Already running?
    if grep -q "^${wid}|" "$WORKER_PIDS_FILE" 2>/dev/null; then
        return
    fi

    # Spawn in background with restart loop
    (
        while true; do
            su-exec www-data:www-data php artisan queue:work --queue="$queue" --sleep=3 --tries=3 --max-time=3600 --max-jobs="$max_jobs"
            echo "worker[$name] ($queue) exited, restarting in 2s..." >> /proc/1/fd/1 2>/dev/null || true
            sleep 2
        done
    ) &
    pid=$!

    echo "${wid}|${pid}" >> "$WORKER_PIDS_FILE"
    echo "supervisor: started worker id=$wid name=$name queue=$queue max_jobs=$max_jobs pid=$pid"
}

# Read desired workers from DB and reconcile with running ones
sync_workers() {
    # Get current desired workers from DB as CSV: id,queue,name,concurrency
    local desired
    desired=$(su-exec www-data:www-data php artisan tinker --execute '
        $workers = \App\Models\Worker::all();
        if ($workers->isEmpty()) {
            // Fallback: one per queue
            echo "__transcode|transcode|Transcode|10\n__subtitle|subtitle|Subtitle|10\n";
        } else {
            foreach ($workers as $w) {
                $queue = match ((string) $w->job_type) {
                    "transcode_media" => "transcode",
                    default => "subtitle",
                };
                $safeName = str_replace([" ","\"",chr(39),"|"], "_", $w->name);
                echo "{$w->id}|{$queue}|{$safeName}|{$w->concurrency}\n";
            }
        }
    ' 2>/dev/null)

    # Collect active IDs from running processes
    local active_ids
    active_ids=$(cut -d'|' -f1 "$WORKER_PIDS_FILE" 2>/dev/null || echo "")

    # Start workers that should be running but aren't
    echo "$desired" | while IFS='|' read -r wid queue name max_jobs; do
        [ -z "$wid" ] && continue
        if ! echo "$active_ids" | grep -q "^${wid}$" 2>/dev/null; then
            start_worker "$wid" "$name" "$queue" "$max_jobs"
        fi
    done

    # Stop workers that are running but shouldn't be
    echo "$active_ids" | while IFS='|' read -r wid; do
        [ -z "$wid" ] && continue
        if ! echo "$desired" | grep -q "^${wid}|" 2>/dev/null; then
            kill_worker "$wid"
        fi
    done
}

# Cleanup all workers on exit
cleanup() {
    echo "supervisor: shutting down all workers..."
    if [ -f "$WORKER_PIDS_FILE" ]; then
        while IFS='|' read -r wid pid; do
            [ -n "$pid" ] && kill "$pid" 2>/dev/null || true
        done < "$WORKER_PIDS_FILE"
        rm -f "$WORKER_PIDS_FILE"
    fi
    exit 0
}
trap cleanup SIGTERM SIGINT

echo "supervisor: started, checking every ${INTERVAL}s for worker changes"
touch "$WORKER_PIDS_FILE"

while true; do
    sync_workers
    sleep "$INTERVAL"
done
