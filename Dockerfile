FROM composer:2 AS composer
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

COPY . .
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php && \
    cp .env.prod.example .env && \
    php artisan key:generate --force && \
    php artisan wayfinder:generate --with-form

FROM oven/bun:1 AS assets
WORKDIR /app

ENV SKIP_WAYFINDER=true

COPY package.json bun.lock ./
RUN bun install --frozen-lockfile

COPY --from=composer /app/resources/js/wayfinder ./resources/js/wayfinder
COPY --from=composer /app/resources/js/routes ./resources/js/routes
COPY --from=composer /app/resources/js/actions ./resources/js/actions
COPY vite.config.ts tsconfig.json ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN bun run build

FROM php:8.5-fpm-bookworm AS runtime
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        mkvtoolnix \
        bash \
        curl \
        postgresql-client \
        gosu \
        supervisor \
        ca-certificates \
        gnupg \
        libpq-dev \
        libzip-dev \
        libicu-dev \
    && curl -fsSL https://repo.jellyfin.org/jellyfin_team.gpg.key | gpg --dearmor -o /etc/apt/trusted.gpg.d/jellyfin.gpg \
    && echo "deb [arch=amd64] https://repo.jellyfin.org/debian bookworm main" > /etc/apt/sources.list.d/jellyfin.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends jellyfin-ffmpeg7 \
    && ln -s /usr/lib/jellyfin-ffmpeg/ffmpeg /usr/local/bin/ffmpeg \
    && ln -s /usr/lib/jellyfin-ffmpeg/ffprobe /usr/local/bin/ffprobe \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j"$(nproc)" \
        pgsql \
        pdo_pgsql \
        bcmath \
        zip \
        intl \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer /app/vendor ./vendor
COPY --from=composer /app/app ./app
COPY --from=composer /app/bootstrap ./bootstrap
COPY --from=composer /app/config ./config
COPY --from=composer /app/database ./database
COPY --from=composer /app/resources ./resources
COPY --from=composer /app/routes ./routes
COPY --from=composer /app/.env.prod.example .env.prod.example
COPY --from=composer /app/composer.json .
COPY --from=composer /app/composer.lock .
COPY --from=composer /app/artisan .
COPY --from=composer /app/public ./public

COPY --from=assets /app/public/build ./public/build

COPY docker/prod/nginx.conf /etc/nginx/nginx.conf
COPY docker/prod/php.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/prod/docker-entrypoint.sh /usr/local/bin/
COPY docker/prod/supervisord.conf /etc/supervisord.conf

RUN mkdir -p bootstrap/cache storage/framework/cache/data \
        storage/framework/sessions storage/framework/views \
        storage/logs public/build /var/log/supervisor && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

RUN cp .env.prod.example .env && \
    php artisan key:generate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan event:cache && \
    php artisan view:cache && \
    rm .env

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
