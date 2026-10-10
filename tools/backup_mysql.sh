#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="$ROOT/storage/backups"
mkdir -p "$BACKUP_DIR"
cat <<'MSG'
Укажите параметры базы Beget через переменные окружения DB_NAME, DB_USER, DB_PASS, DB_HOST.
Пример: DB_HOST=localhost DB_NAME=... DB_USER=... DB_PASS=... tools/backup_mysql.sh
MSG
: "${DB_HOST:=localhost}"
: "${DB_NAME:?DB_NAME required}"
: "${DB_USER:?DB_USER required}"
: "${DB_PASS:?DB_PASS required}"
FILE="$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).sql.gz"
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --single-transaction --routines --triggers "$DB_NAME" | gzip > "$FILE"
echo "Backup saved: $FILE"
