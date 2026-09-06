# CybCademy — Deployment Operations

## Scripts

- `deploy.sh <image-tag>` — rolling restart deployment with health check and automatic previous-tag tracking
- `rollback.sh` — reverts to the previously-deployed tag; does not touch the database (see script's own docblock for why)
- `backup.sh` — secondary, independent daily database backup (encrypted, uploaded to a separate failure domain), complementing the primary managed-provider backup mechanism

## Manual steps not yet automated

The CI/CD pipeline (`.github/workflows/ci-cd.yml`) builds and tests the application image but stops short of pushing to a container registry or triggering a remote deployment — those steps depend on infrastructure specifics (which registry, which host, what credentials) that no design document in this project has specified, since that's an operational decision for whoever provisions the actual Ubuntu host referenced throughout Phase 4.

Until that's wired up, the deployment flow is:

1. CI builds and tests the image on every push to `main`
2. An operator manually pulls the built image to the target host (or pushes it to a registry the host pulls from)
3. An operator runs `./deploy/deploy.sh <tag>` on the host

## Restore drills

`backup.sh`'s final output deliberately reminds whoever runs it that a backup has never been validated until it's been test-restored — this echoes a risk flagged as far back as Phase 7 Section 8 and repeated in that phase's Risks section: "a backup that has never been test-restored is not a validated backup... this should be a recurring calendar item post-launch, not a one-time pre-launch checklist item."

**Recommend:** a quarterly calendar reminder, owned by a named person, to actually restore the most recent backup into a scratch environment and verify the data is intact and queryable. This script does not and should not attempt to automate that away — a human deciding "yes, this restored correctly and the data looks right" is the actual control, not a script exit code.
