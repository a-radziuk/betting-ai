#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${ROOT}/.env"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Error: .env not found at ${ENV_FILE}." >&2
    exit 1
fi

if ! command -v mysqldump >/dev/null 2>&1; then
    echo "Error: mysqldump not found in PATH." >&2
    exit 1
fi

read_env_var() {
    local key="$1"
    local line value

    line="$(grep -E "^${key}=" "$ENV_FILE" 2>/dev/null | tail -n 1 || true)"
    if [[ -z "$line" ]]; then
        return 0
    fi

    value="${line#*=}"
    value="${value//$'\r'/}"

    if [[ ${value:0:1} == '"' && ${value: -1} == '"' ]]; then
        value="${value:1:${#value}-2}"
    elif [[ ${value:0:1} == "'" && ${value: -1} == "'" ]]; then
        value="${value:1:${#value}-2}"
    else
        value="${value%%#*}"
        value="${value%"${value##*[![:space:]]}"}"
    fi

    printf '%s' "$value"
}

DB_HOST="$(read_env_var DB_HOST)"
DB_PORT="$(read_env_var DB_PORT)"
DB_USERNAME="$(read_env_var DB_USERNAME)"
DB_PASSWORD="$(read_env_var DB_PASSWORD)"
DB_DATABASE="$(read_env_var DB_DATABASE)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-root}"

if [[ -z "$DB_DATABASE" ]]; then
    echo "Error: DB_DATABASE is not set in .env." >&2
    exit 1
fi

DUMP_DATE="$(php -r "require '${ROOT}/vendor/autoload.php'; \$app = require '${ROOT}/bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo now(config('app.timezone'))->format('Y-m-d');")"
DUMP_DIR="${ROOT}/storage/dumps"
DUMP_FILE="${DUMP_DIR}/${DB_DATABASE}_${DUMP_DATE}.sql"

mkdir -p "$DUMP_DIR"

mysqldump_args=(
    -h "$DB_HOST"
    -P "$DB_PORT"
    -u "$DB_USERNAME"
    --single-transaction
    --routines
    --triggers
)

if [[ -n "$DB_PASSWORD" ]]; then
    mysqldump_args+=(-p"$DB_PASSWORD")
fi

mysqldump_args+=("$DB_DATABASE")

echo "==> Dumping ${DB_DATABASE} to ${DUMP_FILE}"
mysqldump "${mysqldump_args[@]}" > "$DUMP_FILE"

echo "DUMP_FILE=${DUMP_FILE}"
echo "Done."
