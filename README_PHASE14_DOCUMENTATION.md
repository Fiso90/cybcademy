# CybCademy — Phase 14: Documentation

All eight documents specified in the master prompt's Phase 14 scope, in `docs/`:

| Document | Audience | File |
|---|---|---|
| Developer Guide | Engineers joining the project | `docs/developer-guide.md` |
| Administrator Guide | Organisation Administrators | `docs/administrator-guide.md` |
| User Manual | Employees | `docs/user-manual.md` |
| Installation Guide | Whoever stands up a new environment | `docs/installation-guide.md` |
| Deployment Guide | Ops/DevOps, references the real Phase 13 scripts | `docs/deployment-guide.md` |
| API Documentation | Integration developers | `docs/api-documentation.md` |
| Maintenance Guide | Ongoing operations | `docs/maintenance-guide.md` |
| Troubleshooting Guide | Support, on-call engineers | `docs/troubleshooting-guide.md` |

## Approach

Every document cross-references real, previously-shipped artifacts (specific files, specific test names, specific design-doc sections) rather than describing the system in the abstract — the intent is that these documents stay accurate as long as the underlying code does, since they point *at* the code's own comments and tests rather than duplicating explanations that could drift out of sync.

A deliberate choice worth naming: several documents repeat warnings that have appeared in prior phase READMEs (the RLS/SQLite CI risk, the restore-drill discipline, the unscheduled ASVS assessment, the lesson-completion-without-assessment gap). This is not padding — a person reading only the Maintenance Guide or Troubleshooting Guide, without having read every prior phase's README, should still encounter the warnings relevant to their role. Documentation that assumes its reader has read nine other documents first isn't documentation.

## Known gap in this phase itself

The OpenAPI specification (`docs/openapi.yaml`) referenced by both `README_PHASE8_API_Design.md` and this phase's `api-documentation.md` has never actually been generated as a file — every phase since Phase 8 has referenced it as the authoritative machine-readable contract, but the route definitions across ten epics have never been consolidated into one. This is worth closing before Phase 15's Release checklist, since "API Documentation" as a go-live deliverable arguably isn't complete without it.

## Next Phase

**Phase 15 — Release:** Production checklist, go-live checklist, security audit checklist, performance checklist, backup checklist, release notes, Version 1.0.
