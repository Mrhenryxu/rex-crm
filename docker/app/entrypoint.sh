#!/bin/sh
# Prepares the bind-mounted project on container start, then runs the container command.
# Dependencies cannot be installed at image build time because the project directory is
# mounted over /var/www/html. Every step is idempotent, so restarts are quick.
#
# Only the `rex-crm` service sets RUN_SETUP=1. The queue worker waits for it to be healthy.
set -eu

if [ "${RUN_SETUP:-0}" = "1" ]; then
    if [ ! -f .env ]; then
        echo "[setup] Creating .env from .env.example (local development defaults)"
        cp .env.example .env
    fi

    echo "[setup] composer install"
    composer install --no-interaction --prefer-dist --no-progress

    # Reinstall Node packages only when package-lock.json changed since the last install.
    lock_hash="$(sha256sum package-lock.json | cut -d ' ' -f 1)"
    stamp="node_modules/.package-lock.sha256"
    if [ ! -f "$stamp" ] || [ "$(cat "$stamp")" != "$lock_hash" ]; then
        echo "[setup] npm ci"
        npm ci --no-audit --no-fund
        echo "$lock_hash" > "$stamp"
    fi

    if ! grep -q '^APP_KEY=base64:' .env; then
        echo "[setup] Generating APP_KEY"
        php artisan key:generate --no-interaction
    fi

    echo "[setup] Running migrations"
    php artisan migrate --force --no-interaction

    # Demo data for local development only. The seeder skips itself when contacts already exist.
    if grep -q '^APP_ENV=local' .env; then
        echo "[setup] Seeding demo data"
        php artisan db:seed --no-interaction
    fi

    # A stale "hot" file makes Laravel load assets from a Vite dev server that is not running.
    rm -f public/hot
    echo "[setup] Building frontend assets"
    npm run build

    echo "[setup] Done"
fi

exec "$@"
