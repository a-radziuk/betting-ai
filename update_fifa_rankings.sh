#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

if [[ ! -f artisan ]]; then
    echo "Error: artisan not found in ${ROOT}." >&2
    exit 1
fi

REMOTE_HOST="droplet2"
REMOTE_APP_DIR="/var/www/betgenious.club"
EXPORT_FILENAME="fifa_rankings.json"
LOCAL_EXPORT_FILE="${ROOT}/storage/exports/${EXPORT_FILENAME}"
REMOTE_EXPORT_FILE="/var/www/${EXPORT_FILENAME}"

echo "==> Fetching FIFA rankings"
php artisan fifa:rankings

echo "==> Exporting FIFA rankings to ${LOCAL_EXPORT_FILE}"
php artisan fifa:rankings-export

if [[ ! -f "$LOCAL_EXPORT_FILE" ]]; then
    echo "Error: export file not found at ${LOCAL_EXPORT_FILE}." >&2
    exit 1
fi

echo "==> Uploading ${LOCAL_EXPORT_FILE} to ${REMOTE_HOST}:${REMOTE_EXPORT_FILE}"
scp "$LOCAL_EXPORT_FILE" "${REMOTE_HOST}:${REMOTE_EXPORT_FILE}"

echo "==> Importing FIFA rankings on ${REMOTE_HOST}"
ssh "$REMOTE_HOST" "php ${REMOTE_APP_DIR}/artisan fifa:rankings-import ${REMOTE_EXPORT_FILE}"

echo "==> Clearing page cache on ${REMOTE_HOST}"
ssh "$REMOTE_HOST" "php ${REMOTE_APP_DIR}/artisan pages:cache-clear"

echo "Done."
