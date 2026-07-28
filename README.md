<div align="center">
  <img src=".github/logo.svg" alt="Flowarr" width="96" height="96">
  <h1>Flowarr</h1>
  <p>Self-hosted media library file transformation automation</p>
  <p>
    <a href="#features">Features</a> •
    <a href="#quickstart">Quickstart</a> •
    <a href="#architecture">Architecture</a> •
    <a href="#contributing">Contributing</a>
  </p>
  <p>
    <a href="https://github.com/fais/flowarr/actions"><img src="https://github.com/fais/flowarr/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
    <a href="https://github.com/fais/flowarr/pkgs/container/flowarr"><img src="https://img.shields.io/badge/docker-ghcr.io-blue?logo=docker" alt="Docker"></a>
    <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
  </p>
</div>

Flowarr automates media file transformations for private media servers running Jellyfin/Plex. It watches filesystem directories, runs configurable jobs (transcoding, subtitle extraction), and pauses all processing when Jellyfin streams are active to avoid GPU contention.

Built for the Steam Deck media server problem: transcode h.264 → HEVC for ~60% space savings, extract ASS/SSA subtitles to SRT sidecars for direct-play compatibility.

## Features

- **GPU-Accelerated Transcoding**: HDR → SDR tonemapped HEVC encoding via NVENC (with libx265 fallback)
- **Subtitle Extraction**: Extract text-based subtitles (SRT, ASS, SSA, WebVTT) to sidecar files, strip internal subtitle tracks
- **Subtitle Conversion**: Convert non-SRT subtitle files (VTT, ASS) to SRT
- **Jellyfin Webhook Integration**: Auto-pause all processing when Jellyfin streams are active, resume when they stop
- **Library Management**: Multiple media directories with per-library job configuration and scan intervals
- **Execution Tracking**: Full lifecycle tracking from queued → processing → completed/failed
- **Queue-backed Jobs**: RabbitMQ or database-backed job queues with per-job-type routing
- **Web UI**: Dashboard, library management, execution monitoring via Inertia + React
- **Authentication**: Registration, login, passkeys (WebAuthn), email verification, 2FA/TOTP

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.5, Laravel 13, Fortify |
| Frontend | React 19, Inertia v3, TypeScript, Tailwind v4, shadcn/ui |
| Database | PostgreSQL 18 |
| Queue | RabbitMQ or database driver |
| Cache | Redis |
| Search | Meilisearch |
| Containers | Docker (single image: nginx + PHP-FPM, Alpine-based) |

## Quickstart

### Self-Hosting (Docker)

Pull the prebuilt image from GHCR, configure environment, and start with Docker Compose.

<details>
<summary><b>Prerequisites</b></summary>

- Docker Engine 24+ with Compose plugin
- An `APP_KEY` (generate with `openssl rand -base64 32`)
- A PostgreSQL 18-compatible password
- _(Optional)_ A running Traefik reverse proxy for TLS / homelab routing

> **PostgreSQL 18 note:** The volume mounts at `/var/lib/postgresql` (not `/var/lib/postgresql/data`).
> PG 18+ requires a mount at the parent directory so it can create a version-specific subdirectory,
> which is needed for clean `pg_upgrade` later. See
> [docker-library/postgres#1259](https://github.com/docker-library/postgres/pull/1259).

</details>

#### Setup

```bash
# 1. Pull the compose file and env template
curl -O https://raw.githubusercontent.com/fais/flowarr/main/docker-compose.yml
curl -O https://raw.githubusercontent.com/fais/flowarr/main/.env.example.docker

# 2. Configure
cp .env.example.docker .env
#   Edit .env — at minimum set:
#     APP_KEY=$(openssl rand -base64 32)
#     DB_PASSWORD=<your-password>
#     DOMAIN=<your-domain>         # only needed with Traefik
#     APP_URL=https://<your-domain>

# 3. Start
docker compose up -d
```

Visit `https://<your-domain>/register` (or `http://localhost:8080` without Traefik) to create the first user.

#### Compose reference

The stack runs three services: PostgreSQL 18, Redis 7, and the Flowarr app container.

```yaml
services:
  flowarr-postgres:
    image: postgres:18-alpine
    volumes:
      - postgres-data:/var/lib/postgresql   # PG 18+ needs mount at /var/lib/postgresql, not /data
    healthcheck:
      test: ["CMD", "pg_isready", "-q", "-d", "flowarr", "-U", "flowarr"]
      start_period: 60s   # grace for first-start initdb
      retries: 5

  flowarr-redis:
    image: redis:7-alpine
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]

  flowarr:
    image: ghcr.io/fais/flowarr:latest
    ports:
      - "8080:8080"   # remove for Traefik-only access
    environment:
      APP_KEY:        # required — generate with `openssl rand -base64 32`
      APP_URL:        # https://<your-domain>
      DB_PASSWORD:    # required — matches POSTGRES_PASSWORD above
      RUN_MIGRATIONS: "true"
    volumes:
      - /path/to/media:/media          # writable — Flowarr writes transcoded files & sidecars here
      - flowarr-storage:/var/www/html/storage
```

The full compose file is at [`docker-compose.yml`](docker-compose.yml) in the repo root. Environment variables are documented in [`.env.example.docker`](.env.example.docker).

#### Container details

The Docker image (`ghcr.io/fais/flowarr:latest`) is a single Alpine-based container running both nginx and PHP-FPM.

- **Port**: 8080 (internal), FastCGI proxy to `127.0.0.1:9000`
- **PHP**: 8.5, extensions: pgsql, pdo_pgsql, bcmath, zip, intl, pcntl, redis
- **System tools**: ffmpeg 8.1, mkvtoolnix 99, bash, curl, postgresql-client
- **Build**: 3-stage (composer → assets → runtime), ~465 MB compressed

#### First boot

On first start, PostgreSQL initializes its data directory (can take 30-60s). The healthcheck has `start_period: 60s` to allow for this — Docker won't mark it unhealthy during init.

Once Postgres is healthy, the Flowarr entrypoint runs in order:

1. Creates `.env` from `.env.example.docker` if missing
2. Writes `APP_KEY` from environment into `.env` (auto-generates if empty)
3. Clears and rebuilds Laravel configuration, route, event, and view caches
4. Runs database migrations if `RUN_MIGRATIONS=true` (default)
5. Starts PHP-FPM in background, then nginx in foreground

No manual artisan commands required.

#### APP_KEY persistence

If `APP_KEY` is not set in the environment, one is auto-generated on first start (logged as a warning). **Sessions will be lost on restart** unless you capture the generated key and set it in your `.env`. For permanent deployments, generate one explicitly:

```bash
openssl rand -base64 32
# → SaN+R05iCUuA64GtMh579p/MdA5giQLJw1q8wYQ3oB8=
# Set APP_KEY=base64:SaN+R05iCUuA64GtMh579p/MdA5giQLJw1q8wYQ3oB8= in .env
```

#### Traefik reverse proxy

The compose file includes Traefik v2/v3 discovery labels. To use them:

1. Make sure Traefik is running with an `traefik` Docker network: `docker network create traefik`
2. Set `DOMAIN` in `.env` — this becomes the `Host()` rule
3. Set `APP_URL` to `https://<your-domain>`
4. Remove the `ports:` block from the compose file (Traefik routes internally)
5. Start the stack: `docker compose up -d`

Labels are pre-configured for TLS on the `websecure` entrypoint. The container joins both the `flowarr` internal network and the `traefik` external network.

#### Directory layout (container)

```
/var/www/html/
├── public/          ← Laravel public dir (index.php, build/ assets)
├── storage/         ← persistent (mount as volume)
├── vendor/          ← Composer deps
├── app/ config/ etc ← Laravel app
```

Mount your media library at `/media` (writable — Flowarr writes transcoded files and subtitle sidecars here):

```yaml
volumes:
  - /path/to/your/media:/media
```

### Development (Laravel Sail)

```bash
git clone https://github.com/fais/flowarr
cd flowarr
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail composer setup
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

The app will be available at `http://localhost`.

```bash
./vendor/bin/sail composer run dev    # Starts application at localhost:80
./vendor/bin/sail artisan test   # Run tests
```

## Jellyfin Webhook Integration

1. Install the **Webhook** plugin in Jellyfin
2. Add a webhook pointed at `http://your-flowarr-host/webhooks/jellyfin`
3. Select events: `Playback start` and `Playback stop`
4. (Optional) Set `JELLYFIN_WEBHOOK_TOKEN` in `.env` to secure the endpoint

When a stream starts, all running ffmpeg/mkvmerge processes are paused (SIGSTOP). They resume (SIGCONT) when the stream ends.

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) for the full entity schema, data flow, and implementation plan.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for setup instructions, test commands, and PR workflow.

## License

MIT — see [LICENSE](LICENSE).
