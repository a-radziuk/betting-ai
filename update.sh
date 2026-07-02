#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

if [[ ! -f artisan ]]; then
    echo "Error: artisan not found in ${ROOT}." >&2
    exit 1
fi

echo "==> Pulling latest changes"
git pull

echo "==> Installing PHP dependencies"
composer install --no-interaction

echo "==> Installing Node dependencies"
npm install

echo "==> Building frontend assets"
npm run build

echo "==> Running database migrations"
php artisan migrate

echo "==> Clearing application cache"
php artisan cache:clear

echo "==> Clearing page cache"
php artisan pages:cache-clear

echo "Done."
