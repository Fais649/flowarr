---
name: prod-e2e-test
description: Run an end-to-end test against the production Docker image. Starts the stack, mounts synthetic test media, creates a library, processes all jobs, verifies results, and cleans up.
allowed-tools: Bash(ctx_execute:*)
license: MIT
---

Run the production e2e test against the real Docker image. The test:
1. Generates synthetic test media (if needed)
2. Starts the production Docker stack with test overrides
3. Creates a library for the test media
4. Triggers a scan
5. Runs the queue worker to process all jobs
6. Verifies all executions reached a terminal state
7. Tears down the stack and reports pass/fail

**Prerequisites:**
- Docker Engine 24+ with Compose plugin
- `ffmpeg` installed on the host
- Openspec CLI available
- Port 8089 free (test port)

## Steps

### 1. Generate synthetic test media

Create a temp directory with test media files:

```bash
E2E_DIR=$(mktemp -d /tmp/flowarr-e2e-XXXXXX)
export E2E_DIR

# Generate a 5-second H.264 test video
ffmpeg -y -f lavfi -i testsrc=duration=5:size=128x72:rate=30 \
    -c:v libx264 -preset ultrafast -pix_fmt yuv420p \
    "$E2E_DIR/test_video.mkv" 2>/dev/null

# Generate a simple SRT subtitle file
cat > "$E2E_DIR/test_subs.srt" << 'SRT'
1
00:00:01,000 --> 00:00:04,000
Flowarr E2E Test - subtitle track

2
00:00:04,500 --> 00:00:05,000
End of test content.
SRT
```

Verify files were created:
```bash
ls -la "$E2E_DIR/"
```

### 2. Start the production stack

Start postgres, redis, and the app with the test media mounted:

```bash
cd /home/fais/punk.sys/proj/flowarr

# Create an APP_KEY
E2E_KEY=$(openssl rand -base64 32)
E2E_PASS="e2e_test_pass_$(openssl rand -hex 4)"

# Create a test docker-compose override
cat > /tmp/docker-compose.e2e.yml << YAML
services:
  flowarr-postgres:
    environment:
      POSTGRES_PASSWORD: ${E2E_PASS}
  flowarr:
    image: fais649/flowarr:latest
    build: .
    ports:
      - "8089:8080"
    environment:
      APP_KEY: "base64:${E2E_KEY}"
      APP_URL: "http://localhost:8089"
      APP_ENV: production
      APP_DEBUG: "true"
      DB_HOST: flowarr-postgres
      DB_PORT: "5432"
      DB_DATABASE: flowarr
      DB_USERNAME: flowarr
      DB_PASSWORD: ${E2E_PASS}
      REDIS_HOST: flowarr-redis
      REDIS_PORT: "6379"
      LOG_LEVEL: debug
      RUN_MIGRATIONS: "true"
    volumes:
      - ${E2E_DIR}:/media
      - flowarr-storage:/var/www/html/storage
YAML

export E2E_KEY E2E_PASS

docker compose -f docker-compose.yml -f /tmp/docker-compose.e2e.yml up -d
```

Wait for the stack to become healthy:
```bash
echo "Waiting for stack to become healthy..."
for i in $(seq 1 30); do
    HEALTHY=$(docker inspect --format='{{.State.Health.Status}}' flowarr-postgres 2>/dev/null)
    if [ "$HEALTHY" = "healthy" ]; then
        echo "Postgres healthy after ${i}s"
        break
    fi
    sleep 2
done

for i in $(seq 1 30); do
    HTTP=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8089/up 2>/dev/null)
    if [ "$HTTP" = "200" ]; then
        echo "App healthy after ${i}s"
        break
    fi
    sleep 2
done
```

If the stack fails to start, diagnose and fix before continuing.

### 3. Create a library

Register a user (required to create libraries):

```bash
# Get CSRF token and register a test user
REGISTER_RESPONSE=$(curl -s -c /tmp/e2e-cookies.txt -b /tmp/e2e-cookies.txt \
    -X POST http://localhost:8089/register \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"name":"E2E Test User","email":"e2e@flowarr.test","password":"e2eTestPass123!","password_confirmation":"e2eTestPass123!"}' 2>&1)

echo "$REGISTER_RESPONSE"
```

Create the library pointing at `/media`:

```bash
# Get session from register response
LIBRARY_RESPONSE=$(curl -s -c /tmp/e2e-cookies.txt -b /tmp/e2e-cookies.txt \
    -X POST http://localhost:8089/libraries \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"base_path":"/media","scan_interval":3600}' 2>&1)

echo "$LIBRARY_RESPONSE"
```

If the register call fails (user already exists from a previous run), log in instead.

### 4. Trigger a scan

```bash
# Get the library ID
LIB_ID=$(docker compose -f docker-compose.yml -f /tmp/docker-compose.e2e.yml exec -T flowarr \
    php artisan tinker --execute 'echo \App\Models\Library::first()?->id;' 2>/dev/null)

echo "Library ID: $LIB_ID"

# Trigger scan
docker compose -f docker-compose.yml -f /tmp/docker-compose.e2e.yml exec -T flowarr \
    php artisan scan:libraries 2>&1
```

### 5. Run the queue worker

```bash
echo "Running queue worker to process jobs..."
docker compose -f docker-compose.yml -f /tmp/docker-compose.e2e.yml exec -T flowarr \
    php artisan queue:work --queue=transcode,subtitle --sleep=3 --tries=1 --max-jobs=10 --max-time=120 2>&1
```

### 6. Verify results

Check that all executions reached a terminal state:

```bash
RESULT=$(docker compose -f docker-compose.yml -f /tmp/docker-compose.e2e.yml exec -T flowarr \
    php artisan tinker --execute '
    $total = \App\Models\Execution::count();
    $terminal = \App\Models\Execution::whereIn("status", ["completed", "failed"])->count();
    $stuck = \App\Models\Execution::whereIn("status", ["queued", "processing"])->count();
    echo "Total: $total\nTerminal: $terminal\nStuck: $stuck\n";
    if ($stuck > 0) {
        echo "FAIL: $stuck execution(s) stuck in non-terminal state.\n";
        \App\Models\Execution::whereIn("status", ["queued", "processing"])->get()->each(function($e) {
            echo "  - [{$e->status}] {$e->file_path}\n";
        });
        exit(1);
    }
    echo "PASS: All $total executions reached terminal state.\n";
    ' 2>&1)

echo "$RESULT"

PASS=$(echo "$RESULT" | grep -c "PASS" || true)
```

Also verify the test media was processed correctly — check for output files from transcode:

```bash
echo "=== Container media dir after processing ==="
docker compose -f docker-compose.yml -f /tmp/docker-compose.e2e.yml exec flowarr \
    ls -la /media/ 2>&1

echo "=== Execution summary ==="
docker compose -f docker-compose.yml -f /tmp/docker-compose.e2e.yml exec -T flowarr \
    php artisan tinker --execute '
    echo "Status breakdown:\n";
    \App\Models\Execution::selectRaw("status, count(*) as cnt")
        ->groupBy("status")
        ->get()
        ->each(function($r) { echo "  {$r->status}: {$r->cnt}\n"; });
    ' 2>&1
```

### 7. Cleanup

Always tear down:

```bash
docker compose -f docker-compose.yml -f /tmp/docker-compose.e2e.yml down -v 2>&1
rm -f /tmp/e2e-cookies.txt /tmp/docker-compose.e2e.yml
rm -rf "$E2E_DIR"
```

### 8. Report

```bash
if [ "$PASS" -gt 0 ]; then
    echo "✓ E2E TEST PASSED - Production image is healthy"
else
    echo "✗ E2E TEST FAILED - Production image has issues"
    exit 1
fi
```

## On Failure

If any step fails:
1. Diagnose the issue (check logs, inspect container state)
2. Fix the root cause in the codebase
3. Commit the fix
4. Re-run the e2e test from step 1
5. Repeat until the full pipeline passes
