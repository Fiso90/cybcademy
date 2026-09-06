#!/usr/bin/env bash
#
# CybCademy — Deployment Script
#
# Implements the rolling/blue-green deployment intent referenced in
# Phase 4 Section 11 and Phase 9 Section 6 ("Rollback strategy... means
# any production release can be reverted to the previous tagged version
# without a database rollback in the common case; migrations are written
# to be backward-compatible for at least one release cycle"). This script
# is deliberately simple (single-host Docker Compose) rather than a full
# orchestrator (Kubernetes, ECS) - matches the confirmed v1.0 scale
# (single-region, Ubuntu host) rather than over-building for scale the
# platform doesn't have yet.
#
# Usage: ./deploy/deploy.sh <image-tag>

set -euo pipefail

IMAGE_TAG="${1:?Usage: deploy.sh <image-tag>}"
COMPOSE_FILE="docker-compose.yml"
PREVIOUS_TAG_FILE=".last-deployed-tag"

echo "==> Deploying CybCademy image tag: ${IMAGE_TAG}"

# Record the currently-running tag before we touch anything, so
# rollback.sh has something concrete to revert to.
if [ -f "${PREVIOUS_TAG_FILE}" ]; then
    cp "${PREVIOUS_TAG_FILE}" ".previous-deployed-tag"
fi
echo "${IMAGE_TAG}" > "${PREVIOUS_TAG_FILE}"

echo "==> Pulling new image"
docker compose -f "${COMPOSE_FILE}" pull app queue-default queue-phishing-sends queue-audit-exports scheduler

echo "==> Running database migrations"
# Runs BEFORE the app container is fully cut over, expecting migrations
# to be backward-compatible with the still-running previous version for
# the duration of the rolling restart below (Phase 9 Section 6's
# expand/contract migration pattern) - a destructive migration (dropping
# a column the old code still reads) would violate that pattern and
# should never ship in the same release as removing the code that used it.
docker compose -f "${COMPOSE_FILE}" run --rm app php artisan migrate --force

echo "==> Rolling restart of app and worker containers"
# --no-deps avoids recreating Redis/Nginx unnecessarily; one service at a
# time rather than `docker compose up -d` for everything at once, so
# Nginx (which stays up throughout) never has zero healthy app backends.
for service in app queue-default queue-phishing-sends queue-audit-exports scheduler; do
    echo "    -> restarting ${service}"
    docker compose -f "${COMPOSE_FILE}" up -d --no-deps --build "${service}"
done

echo "==> Verifying health"
sleep 5
if ! curl -sf http://localhost:8080/ > /dev/null; then
    echo "!! Health check failed after deploy. Consider running ./deploy/rollback.sh"
    exit 1
fi

echo "==> Deployment complete: ${IMAGE_TAG}"
