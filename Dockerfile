# Stage 1: Composer dependencies + Wayfinder types
FROM composer:latest AS composer
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

# Copy app source to generate wayfinder types
COPY . .
# Create minimal .env so artisan can bootstrap, then run scripts and generate wayfinder types
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php && \
    cp .env.example.docker .env && \
    php artisan key:generate --force && \
    php artisan wayfinder:generate

# Stage 2: Frontend assets
FROM node:22-alpine AS assets
WORKDIR /app

# Wayfinder types are pre-generated in composer stage — skip PHP call during build
ENV SKIP_WAYFINDER=true

COPY package.json package-lock.json ./
RUN npm ci

COPY --from=composer /app/resources/js/routes ./resources/js/routes
COPY --from=composer /app/resources/js/actions ./resources/js/actions
COPY vite.config.ts tsconfig.json ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN npm run build

# Stage 3: Runtime image
FROM php:8.5-fpm-alpine AS runtime
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
        nginx \
        ffmpeg \
        mkvtoolnix \
        bash \
        curl \
        postgresql-client \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN apk add --no-cache --virtual .php-ext-deps \
        linux-headers \
        autoconf \
        g++ \
        make \
        postgresql-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pgsql \
        pdo_pgsql \
        bcmath \
        zip \
        intl \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .php-ext-deps \
    && apk add --no-cache libzip \
    && rm -rf /tmp/pear

# Copy application from composer stage
COPY --from=composer /app/vendor ./vendor
COPY --from=composer /app/app ./app
COPY --from=composer /app/bootstrap ./bootstrap
COPY --from=composer /app/config ./config
COPY --from=composer /app/database ./database
COPY --from=composer /app/resources ./resources
COPY --from=composer /app/routes ./routes
COPY --from=composer /app/.env.example.docker .env.example.docker
COPY --from=composer /app/composer.json .
COPY --from=composer /app/composer.lock .
COPY --from=composer /app/artisan .
COPY --from=composer /app/public ./public

# Overlay built assets from assets stage
COPY --from=assets /app/public/build ./public/build

# Copy production config files
COPY docker-production/nginx.conf /etc/nginx/http.d/default.conf
COPY docker-production/php.ini /usr/local/etc/php/conf.d/production.ini
COPY docker-production/docker-entrypoint.sh /usr/local/bin/

# Create required Laravel directories
RUN mkdir -p bootstrap/cache storage/framework/cache/data \
        storage/framework/sessions storage/framework/views \
        storage/logs public/build && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

# Pre-warm caches (config cache will be rebuilt at runtime after .env substitution)
RUN cp .env.example.docker .env && \
    php artisan key:generate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan event:cache && \
    php artisan view:cache && \
    rm .env

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
