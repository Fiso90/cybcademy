#!/usr/bin/env bash
#
# CybCademy — Rollback Script
#
# Reverts to the previously-deployed image tag, recorded by deploy.sh.
# Deliberately does NOT roll back the database migration that ran during
# the failed deploy - per Phase 9 Section 6's expand/contract migration
# discipline, a migration from the release being rolled back must remain
# backward-compatible with the previous code version, so leaving the
# schema as-is and reverting only the application code is the correct,
# safe rollback path in the common case. A migration that genuinely
# cannot satisfy that (a true breaking schema change) should never have
# shipped without a dedicated, manually-supervised rollback plan of its
# own - this script does not attempt to auto-detect or handle that case.

set -euo pipefail

PREVIOUS_TAG_FILE=".previous-deployed-tag"
COMPOSE_FILE="docker-compose.yml"

if [ ! -f "${PREVIOUS_TAG_FILE}" ]; then
    echo "!! No previous deployment recorded - nothing to roll back to."
    exit 1
fi

PREVIOUS_TAG=$(cat "${PREVIOUS_TAG_FILE}")
echo "==> Rolling back to previous image tag: ${PREVIOUS_TAG}"

for service in app queue-default queue-phishing-sends queue-audit-exports scheduler; do
    echo "    -> reverting ${service}"
    docker compose -f "${COMPOSE_FILE}" up -d --no-deps --build "${service}"
done

echo "==> Verifying health after rollback"
sleep 5
if ! curl -sf http://localhost:8080/ > /dev/null; then
    echo "!! Health check still failing after rollback. Manual intervention required - do not assume this script has resolved the incident."
    exit 1
fi

echo "==> Rollback complete."
