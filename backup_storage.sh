#!/bin/bash
# ==============================================================================
# Cravelo / Yemek Kurye Sistemi — Automated Storage Backup Script
# Author: Mustafa Güreşir
# Target: Daily JSON database backup without *.lock files
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/cravelo}"
SOURCE_DIR="${SCRIPT_DIR}/storage"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="cravelo_storage_backup_${TIMESTAMP}.tar.gz"

# Create backup directory if it does not exist
mkdir -p "$BACKUP_DIR"

# Verify source storage directory exists
if [ ! -d "$SOURCE_DIR" ]; then
    echo "[HATA] Kaynak storage dizini bulunamadi: $SOURCE_DIR" >&2
    exit 1
fi

# Create compressed tarball excluding runtime lock files
tar --exclude='*.lock' -czf "${BACKUP_DIR}/${FILENAME}" -C "$SOURCE_DIR" .

# Auto-prune backups older than 7 days to conserve disk space
find "$BACKUP_DIR" -type f -name "cravelo_storage_backup_*.tar.gz" -mtime +7 -delete

echo "[$(date '+%Y-%m-%d %H:%M:%S')] [OK] storage/ JSON veri deposu yedeklendi: ${BACKUP_DIR}/${FILENAME}"
