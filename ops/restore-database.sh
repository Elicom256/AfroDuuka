#!/usr/bin/env bash
set -Eeuo pipefail

if [[ $# -ne 2 ]]; then
    printf 'Usage: %s <spaces-object-key> <existing-target-database>\n' "$0" >&2
    exit 2
fi

object_key="$1"
target_database="$2"
read -r -p "Restore $object_key into $target_database? Type the target database name to continue: " confirmation
if [[ "$confirmation" != "$target_database" ]]; then
    printf 'Restore cancelled.\n' >&2
    exit 1
fi

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="${COMPOSE_FILE:-docker-compose.prod.yml}"
if [[ "$compose_file" != /* ]]; then
    compose_file="$repo_root/$compose_file"
fi

compose=(docker compose --env-file "$repo_root/api/.env" -f "$compose_file")

"${compose[@]}" exec -T backend php artisan duukaflow:database:backup \
    --download="$object_key" \
    | "${compose[@]}" exec -T pgsql sh -lc \
        'pg_restore --clean --if-exists --no-owner --username="$POSTGRES_USER" --dbname="$1" -' \
        sh "$target_database"