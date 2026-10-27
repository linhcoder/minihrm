#!/usr/bin/env sh
set -eu
: "${BACKUP_DIR:?Set BACKUP_DIR outside the public web root}"
umask 077
mkdir -p "$BACKUP_DIR"
stamp=$(date -u +%Y%m%dT%H%M%SZ)
# MYSQL_PWD is supplied by a private environment file; never print it.
docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysqldump -u "$MYSQL_USER" --single-transaction --routines --triggers "$MYSQL_DATABASE"' | gzip > "$BACKUP_DIR/mysql-$stamp.sql.gz"
docker compose run --rm --no-deps -T --user root php tar czf - writable > "$BACKUP_DIR/writable-$stamp.tar.gz"
sha256sum "$BACKUP_DIR/mysql-$stamp.sql.gz" "$BACKUP_DIR/writable-$stamp.tar.gz" > "$BACKUP_DIR/sha256-$stamp.txt"
echo 'Backup written. Encrypt and copy to off-host storage, then perform a restore drill.'
