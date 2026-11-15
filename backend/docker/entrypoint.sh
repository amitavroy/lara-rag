#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --ansi
fi

exec "$@"
