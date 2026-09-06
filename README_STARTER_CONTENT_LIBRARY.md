# CybCademy — Starter Content Library

Directly addresses the gap Phase 15's Release document called "arguably the single largest gap of the entire project": **CybCademy shipped through all 15 planned phases with zero course content**, meaning a freshly onboarded tenant had a fully functional platform and nothing for an employee to actually train on.

## What this adds

- **Three real courses**, written as genuine cybersecurity awareness training content, not placeholder text:
  - *Recognising Phishing Attacks* (3 lessons, 3-question assessment) — onboarding default
  - *Passwords and Multi-Factor Authentication* (2 lessons, 2-question assessment) — onboarding default
  - *Social Engineering Beyond Email* (2 lessons, no assessment — deliberately, see below)
- **Three platform-wide phishing simulation templates**, using the shared-template pattern established in Epic E4 (`tenant_id` null, visible to every tenant)
- **A new command**, `php artisan content:seed-starter-library {tenant}`, run per-tenant rather than globally
- **A new column**, `courses.is_onboarding_default` — this closes a *second*, smaller gap: Epic E2's `AssignOnboardingCourses` listener has referenced this column since Phase 10, explicitly flagged in that epic's own README as "not yet in the Epic E2 migrations... assumes that column will exist by the time E10 lands." Epic E10 shipped without adding it. It exists now, because this is the first work that actually needed it to be real rather than a documented assumption.

## Why per-tenant seeding, not a schema change

The architecturally "pure" version of this fix would extend the same nullable-`tenant_id`, shared-content pattern already used for `phishing_templates` (Epic E4) and `knowledge_base_articles` (Epic E10) to `courses` as well — platform-authored courses visible to every tenant, no seeding required per tenant.

That was deliberately not done here. `courses.tenant_id` has been `NOT NULL` since Phase 5's original database design, and `BelongsToTenant`'s global scope assumes every tenant-scoped model always has a tenant_id present — loosening that for one table is a real architecture decision with knock-on effects (course assignment, certificates, and Human Risk Score calculations all reference `course_id` and would need to handle a course belonging to no specific tenant). That's worth its own considered design pass, not something to fold into a content-writing task. The per-tenant seed command solves the immediate "new tenant has nothing to train on" problem today, cleanly, without touching that model — and doesn't foreclose the larger architectural change later if it turns out to be worth it once there's a second or third *reason* to want shared courses (e.g. real course marketplace, not just starter content).

## What this does NOT solve

- **Volume.** Three courses is a starter library, not a comprehensive one. Phase 1's Business Case envisioned a platform competing with KnowBe4/Proofpoint on content breadth — this is nowhere near that scale, and shouldn't be represented as such.
- **Localisation.** English only, consistent with the v1.0 scope decided in Phase 2, but worth remembering this is still a real gap for the African-market positioning central to the whole product thesis from Phase 1.
- **The *Social Engineering* course has no assessment**, which means — per the still-open gap flagged since Phase 11 — an employee completing it has no way to have `course_assignments.status` transition to `completed`. This wasn't worked around by artificially bolting on a quiz; it's left as a real, visible instance of that known gap, arguably making the case for fixing it more concrete than an abstract README mention ever did.

## Verification

`tests/Feature/Content/StarterContentLibraryTest.php` proves this isn't just database rows: a seeded assessment is actually gradeable end-to-end through the real Epic E2 `AssessmentService`, achieving a 100% score when answered correctly — confirming the seeded `correct_answer` data is internally consistent, not just present.
