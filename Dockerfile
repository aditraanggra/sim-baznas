# syntax=docker/dockerfile:1

ARG FRANKENPHP_VERSION=1-php8.4-alpine
ARG NODE_VERSION=22-slim

# ---- Tahap 1: build aset frontend ----
FROM node:${NODE_VERSION} AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --prefer-offline --no-audit --fetch-retries=5 --fetch-retry-mintimeout=20000 --fetch-retry-maxtimeout=120000
COPY vite.config.js tsconfig.json components.json ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---- Tahap 2: dependensi PHP (tanpa dev) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
ENV COMPOSER_MAX_PARALLEL_HTTP=4
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --ignore-platform-reqs \
    || composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --ignore-platform-reqs

# ---- Tahap 3: runtime FrankenPHP ----
FROM dunglas/frankenphp:${FRANKENPHP_VERSION} AS app

RUN install-php-extensions pdo_pgsql gd zip intl bcmath opcache pcntl redis \
    && rm -rf /tmp/* /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { \
      echo 'expose_php=0'; \
      echo 'memory_limit=256M'; \
      echo 'upload_max_filesize=20M'; \
      echo 'post_max_size=25M'; \
      echo 'date.timezone=Asia/Jakarta'; \
      echo 'opcache.enable=1'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.max_accelerated_files=20000'; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction \
    && sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache /data/caddy /config/caddy \
    && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    RUN_MIGRATIONS=false

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD wget -qO- http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
