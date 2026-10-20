############################################
# Base: PHP 8.5 on Debian with FrankenPHP
############################################
FROM serversideup/php:8.5-frankenphp AS base

USER root

RUN install-php-extensions intl bcmath

############################################
# Composer dependencies and application code
############################################
FROM base AS composer

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# The install layer stays cached until composer.json or composer.lock changes.
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY . .

RUN composer dump-autoload \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize

############################################
# Frontend assets and the SSR bundle
############################################
FROM base AS frontend

# Bun matches the version CI builds with; Node runs the Vite CLI's node shebang.
COPY --from=oven/bun:1.3.14 /usr/local/bin/bun /usr/local/bin/bun
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node

WORKDIR /app

COPY package.json bun.lock .npmrc ./

RUN bun install --frozen-lockfile

# The Wayfinder Vite plugin runs `php artisan wayfinder:generate`, so the build needs vendor and the app code.
COPY --from=composer /app /app

ARG VITE_APP_NAME="Laravel Tasks"

RUN bun run build:ssr

############################################
# Production image
############################################
FROM base AS deploy

# The Inertia SSR server runs on Node.
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node

COPY --from=composer --chown=www-data:www-data /app /var/www/html
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build
COPY --from=frontend --chown=www-data:www-data /app/bootstrap/ssr /var/www/html/bootstrap/ssr

USER www-data

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8080", "--admin-port=2019", "--max-requests=500"]

HEALTHCHECK --start-period=10s CMD ["healthcheck-octane"]
