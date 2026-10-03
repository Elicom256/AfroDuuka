#!/usr/bin/env bash
set -Eeuo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
backup_script="$repo_root/ops/backup-database.sh"
compose_file="${COMPOSE_FILE:-docker-compose.prod.yml}"
schedule="${BACKUP_CRON_EXPRESSION:-17 2 * * *}"
log_file="$repo_root/storage/logs/db-backup.log"
mkdir -p "$(dirname "$log_file")"

cron_entry="$schedule cd \"$repo_root\" && COMPOSE_FILE=\"$compose_file\" /bin/bash \"$backup_script\" >> \"$log_file\" 2>&1"
current_crontab="$(crontab -l 2>/dev/null || true)"

if grep -Fq "$backup_script" <<< "$current_crontab"; then
    printf 'A database backup cron entry already references %s\n' "$backup_script"
    exit 0
fi

{
    if [[ -n "$current_crontab" ]]; then
        printf '%s\n' "$current_crontab"
    fi
    printf '%s\n' "$cron_entry"
} | crontab -

printf 'Installed daily database backup at %s\n' "$schedule"