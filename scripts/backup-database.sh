#!/usr/bin/env bash
# Dumps the MySQL database from the running "mysql" container, compresses it, and
# prunes backups older than RETENTION_DAYS. Reads DB credentials from the root
# .env (the same file docker-compose.yml uses), so it never hardcodes a password.
#
# Usage: ./scripts/backup-database.sh
# Cron example (mini PC, daily at 02:00): 0 2 * * * /path/to/hris-apic/scripts/backup-database.sh >> /var/log/hris-backup.log 2>&1
set -euo pipefail

cd "$(dirname "$0")/.."

RETENTION_DAYS="${RETENTION_DAYS:-14}"
BACKUP_DIR="$(pwd)/backups"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
OUT_FILE="${BACKUP_DIR}/hris_apic_${TIMESTAMP}.sql.gz"

if [[ ! -f .env ]]; then
    echo "Root .env not found — run this from the hris-apic project root." >&2
    exit 1
fi

# Read only the two keys we need, rather than `source .env` — that file also sets
# UID, which collides with bash's own readonly $UID and aborts the script.
DB_DATABASE="$(grep -E '^DB_DATABASE=' .env | cut -d= -f2-)"
DB_ROOT_PASSWORD="$(grep -E '^DB_ROOT_PASSWORD=' .env | cut -d= -f2-)"

mkdir -p "$BACKUP_DIR"

echo "==> Dumping database '${DB_DATABASE}' to ${OUT_FILE}"
# MYSQL_PWD instead of -p"..." — the password stays out of `ps aux` on shared hosts.
docker compose exec -T -e MYSQL_PWD="${DB_ROOT_PASSWORD}" mysql mysqldump \
    -uroot \
    --single-transaction --quick --routines --triggers \
    "${DB_DATABASE}" | gzip > "$OUT_FILE"

if [[ ! -s "$OUT_FILE" ]]; then
    echo "Backup file is empty — something went wrong." >&2
    exit 1
fi

echo "==> Backup written: $(du -h "$OUT_FILE" | cut -f1)"

echo "==> Pruning backups older than ${RETENTION_DAYS} days"
find "$BACKUP_DIR" -name 'hris_apic_*.sql.gz' -mtime "+${RETENTION_DAYS}" -print -delete

echo "Done."
