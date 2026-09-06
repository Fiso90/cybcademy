#!/usr/bin/env bash
#
# CybCademy — Database Backup Script
#
# Implements Phase 7 Section 8 (Backups): "Automated daily full backups...
# Backup encryption at rest, backups stored in a separate failure domain
# from the primary database." Assumes a managed PostgreSQL provider's
# native backup/PITR feature is the PRIMARY backup mechanism (per Phase 4
# Section 11's architecture decision to use a managed/dedicated Postgres
# host) - this script is a SECONDARY, independent backup, specifically so
# a single provider outage or misconfiguration doesn't leave zero
# recoverable backups. Two independent backup mechanisms, not one
# mechanism run twice.
#
# Usage: ./deploy/backup.sh (intended to run via cron/scheduler, daily)

set -euo pipefail

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="cybcademy-${TIMESTAMP}.sql.gz"
BACKUP_DIR="/tmp/cybcademy-backups"
RETENTION_DAYS=30

mkdir -p "${BACKUP_DIR}"

echo "==> Dumping database"
# Uses pg_dump against the same DB_HOST/DB_DATABASE the app itself
# connects to (read from .env), via the least-privileged application
# role - NOT a superuser dump, consistent with the least-privilege
# principle established for the audit_logs table back in Epic E1.
source .env
PGPASSWORD="${DB_PASSWORD}" pg_dump \
    -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" \
    --no-owner --no-privileges \
    | gzip > "${BACKUP_DIR}/${BACKUP_FILE}"

echo "==> Encrypting backup at rest"
# Encryption per Phase 7 Section 8 - GPG symmetric encryption shown here
# as the concrete, minimal implementation; a production deployment should
# use a managed KMS key rather than a shared passphrase in an environment
# variable, flagged honestly as a hardening item for whoever operationalises
# this script rather than a finished, audit-ready implementation.
gpg --batch --yes --passphrase "${BACKUP_ENCRYPTION_PASSPHRASE:?Set BACKUP_ENCRYPTION_PASSPHRASE}" \
    --symmetric --cipher-algo AES256 \
    -o "${BACKUP_DIR}/${BACKUP_FILE}.gpg" "${BACKUP_DIR}/${BACKUP_FILE}"
rm "${BACKUP_DIR}/${BACKUP_FILE}"

echo "==> Uploading to separate failure domain (S3-compatible / R2)"
# "Separate failure domain from the primary database" (Phase 7 Section 8)
# - uploads to the same R2/S3 bucket family used for file storage
# (Phase 4 Section 10), but a DEDICATED bucket/prefix with its own
# lifecycle policy, not commingled with tenant-uploaded files.
aws s3 cp "${BACKUP_DIR}/${BACKUP_FILE}.gpg" \
    "s3://cybcademy-db-backups/${BACKUP_FILE}.gpg" \
    --endpoint-url "${AWS_ENDPOINT}"

rm "${BACKUP_DIR}/${BACKUP_FILE}.gpg"

echo "==> Pruning local backups older than ${RETENTION_DAYS} days"
find "${BACKUP_DIR}" -name "*.gpg" -mtime "+${RETENTION_DAYS}" -delete

echo "==> Backup complete: ${BACKUP_FILE}.gpg"
echo ""
echo "REMINDER (Phase 7 Section 8 / Risks): a backup that has never been"
echo "test-restored is not a validated backup. This script does NOT run a"
echo "restore drill - that must be a separately scheduled, recurring"
echo "operational task, not something to assume happens because backups"
echo "are running successfully."
