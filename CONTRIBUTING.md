# Contributing to Flowarr

## Setup

1. Fork and clone the repo
2. Copy `.env.example` to `.env` and configure
3. Start the environment: `./vendor/bin/sail up -d`
4. Install dependencies: `./vendor/bin/sail composer install` && `./vendor/bin/sail bun install`
5. Run migrations: `./vendor/bin/sail artisan migrate`
6. Build assets: `./vendor/bin/sail bun run build`

## Development

- Start Vite dev server: `./vendor/bin/sail bun run dev`
- Run PHP tests: `./vendor/bin/sail artisan test --compact`
- Run frontend tests: `./vendor/bin/sail bun run test`
- Run browser tests: `npm run test:browser` (after seeding: `vendor/bin/sail artisan db:seed --class=BrowserTestSeeder`)
- Format PHP: `./vendor/bin/sail bin pint`
- Format JS/TS: `npm run format`
- Lint JS/TS: `npm run lint`
- Static analysis: `./vendor/bin/sail bash -c './vendor/bin/phpstan'`
- TypeScript check: `npm run types:check`

## Pull Request Process

1. Create a feature branch from `main`
2. Make your changes
3. Ensure all tests pass
4. Run linting and formatting
5. Update documentation if needed
6. Submit a pull request with a clear description
