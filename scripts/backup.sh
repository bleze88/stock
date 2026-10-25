#!/bin/bash
# Sauvegarde nocturne : base SQLite (via .backup, sûr avec WAL) + uploads.
set -euo pipefail

APP_ROOT=/var/www/asso-stock
BACKUP_DIR=/var/backups/asso-stock
TS=$(date +%Y%m%d-%H%M%S)

mkdir -p "$BACKUP_DIR"

sqlite3 "${APP_ROOT}/storage/database/stock.sqlite" ".backup '${BACKUP_DIR}/stock-${TS}.sqlite'"
tar czf "${BACKUP_DIR}/uploads-${TS}.tar.gz" -C "${APP_ROOT}/storage" uploads

find "$BACKUP_DIR" -type f -mtime +30 -delete

echo "Sauvegarde terminee : ${BACKUP_DIR}/stock-${TS}.sqlite"
