#!/bin/sh
set -e

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

if [ ! -f vendor/autoload.php ]; then
    echo "vendor directory not found. Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
    chown -R "$(stat -c '%u:%g' composer.json)" vendor
fi

chown -R www-data:www-data storage bootstrap/cache

rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
    export APP_KEY
    sed -i "s|^APP_KEY=.*$|APP_KEY=$APP_KEY|" .env
fi

exec "$@"
