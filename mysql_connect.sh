#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${ROOT}/.env"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Error: .env not found at ${ENV_FILE}." >&2
    exit 1
fi

if ! command -v mysql >/dev/null 2>&1; then
    echo "Error: mysql client not found in PATH." >&2
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

mysql_args=(
    -h "$DB_HOST"
    -P "$DB_PORT"
    -u "$DB_USERNAME"
)

if [[ -n "$DB_PASSWORD" ]]; then
    mysql_args+=(-p"$DB_PASSWORD")
fi

if [[ -n "$DB_DATABASE" ]]; then
    mysql_args+=("$DB_DATABASE")
fi

exec mysql "${mysql_args[@]}"
