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
    <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
  </p>
</div>

Flowarr automates media file transformations for private media servers running Jellyfin/Plex. It watches filesystem directories, runs configurable jobs (transcoding, subtitle extraction), and pauses all processing when Jellyfin streams are active to avoid GPU contention.

Built for the Steam Deck media server problem: transcode h.264 → HEVC for ~60% space savings, extract ASS/SSA subtitles to SRT sidecars for direct-play compatibility.

## Features

- **GPU-Accelerated Transcoding**: HDR→SDR tonemapped HEVC encoding via NVENC (with libx265 fallback)
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
| Containers | Laravel Sail (Docker Compose) |

## Quickstart

```bash
git clone https://github.com/fais/flowarr
cd flowarr
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

The app will be available at `http://localhost`.

### Development

```bash
./vendor/bin/sail bun run dev    # Vite dev server with hot reload
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
