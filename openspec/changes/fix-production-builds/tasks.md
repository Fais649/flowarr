## 1. Environment Configuration

- [x] 1.1 Update `.env.example.docker` to set `DB_HOST=flowarr-postgres`
- [x] 1.2 Verify all database-related defaults in `.env.example.docker` match docker-compose service names

## 2. Entrypoint Script Updates

- [x] 2.1 Add `php artisan config:clear` before `php artisan optimize` in `docker-production/docker-entrypoint.sh`
- [x] 2.2 Modify entrypoint to check if critical environment variables (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD) are already set
- [x] 2.3 If critical env vars are set, skip creating `.env` from example file or generate `.env` without overriding those values
- [x] 2.4 Add logging to entrypoint to indicate which configuration source is being used (Docker env vars vs .env file)

## 3. Verification

- [ ] 3.1 Test production build locally with docker-compose to verify database connection succeeds
- [ ] 3.2 Verify web application is reachable and serves requests without errors
- [ ] 3.3 Test environment variable override by setting custom DB_HOST in docker-compose
- [ ] 3.4 Verify config cache is properly cleared and rebuilt on container restart
