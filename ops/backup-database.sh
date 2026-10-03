#!/usr/bin/env bash
set -Eeuo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="${COMPOSE_FILE:-docker-compose.prod.yml}"
if [[ "$compose_file" != /* ]]; then
    compose_file="$repo_root/$compose_file"
fi

compose=(docker compose --env-file "$repo_root/api/.env" -f "$compose_file")

"${compose[@]}" exec -T pgsql sh -lc \
    'pg_dump --format=custom --no-owner --username="$POSTGRES_USER" --dbname="$POSTGRES_DB"' \
    | "${compose[@]}" exec -T backend php artisan duukaflow:database:backup --stdin