FROM node:22-bookworm-slim AS node

# ---- dev: PHP + Node, source is bind-mounted ----
FROM php:8.4-cli-bookworm AS dev

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath intl pcntl pdo_pgsql redis zip
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Node is needed alongside PHP: the Wayfinder Vite plugin shells out to `php artisan`.
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# ---- build: production vendor + compiled assets ----
FROM dev AS build

COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && npm ci \
    && npm run build \
    && rm -rf node_modules

# ---- prod: FrankenPHP serving public/ ----
FROM dunglas/frankenphp:1-php8.4-bookworm AS prod

RUN install-php-extensions bcmath intl opcache pcntl pdo_pgsql redis zip
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini

ENV SERVER_NAME=:80

WORKDIR /app
COPY --from=build --chown=www-data:www-data /var/www/html /app
