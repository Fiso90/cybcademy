# CybCademy — Phase 6: UI Design Guide

**Product:** CybCademy — Human Cyber Risk Management & Cybersecurity Awareness Platform
**Developed by:** Solunar Informatics
**Document Version:** 1.0
**Date:** 28 July 2026
**Status:** Draft for Approval
**Depends on:** Phase 1–5 (approved)
**Frontend Stack:** Laravel Blade + Bootstrap 5 + Alpine.js (per Phase 4)

---

## 1. Design Direction

CybCademy sits at the intersection of two audiences with different needs from the same interface: **compliance/audit professionals** who need density, precision, and trustworthy data presentation, and **ordinary employees** taking a five-minute training module between meetings, who need clarity and zero friction. The design system is built to serve both without feeling like two different products.

**Signature element:** the **Risk Ring** — a consistent circular progress/score motif used everywhere a Human Risk Score appears (employee card, department heatmap, executive dashboard hero, certificate issuance). It gives the platform's core metric a single, recognisable visual identity rather than reinventing "a number in a box" per screen.

We deliberately avoid the generic "AI product" look — no warm cream/serif/terracotta, no near-black-with-neon-accent, no broadsheet hairlines. This is an enterprise risk and compliance tool; it should read as closer to a Bloomberg terminal's calm confidence than a consumer SaaS landing page.

---

## 2. Design System

### 2.1 Colour Palette

| Token | Hex | Usage |
|---|---|---|
| `--cyb-navy-900` | `#0E1B2E` | Primary dark surface (Dark Mode background, sidebar in Light Mode) |
| `--cyb-navy-700` | `#1B3252` | Secondary dark surface, hover states on navy |
| `--cyb-slate-100` | `#F4F6F9` | Light Mode background |
| `--cyb-slate-300` | `#D9DFE7` | Borders, dividers |
| `--cyb-teal-500` | `#1FA6A0` | Primary accent — brand, primary buttons, active nav state |
| `--cyb-amber-500` | `#E8A33D` | Medium risk / warning state |
| `--cyb-red-600` | `#D14343` | High risk / critical / destructive actions |
| `--cyb-green-600` | `#2E9E5B` | Low risk / success / completion state |

Risk-tier colours (amber/red/green) are calibrated for WCAG AA contrast against both `--cyb-slate-100` and `--cyb-navy-900` — verified per-mode, not assumed to translate automatically between Light and Dark.

**Rationale:** Teal reads as security/trust without the overused terracotta/orange of typical AI-product defaults, and pairs cleanly with navy for an "enterprise console" feel consistent with the brief's stated inspiration (Azure Portal, GitHub, Atlassian).

### 2.2 Typography

| Role | Typeface | Notes |
|---|---|---|
| Display / Headings | **Inter Tight** (or system equivalent) | Geometric, confident, used at restrained sizes — this is a data tool, not a marketing page |
| Body | **Inter** | High legibility at small sizes for dense dashboard/table content |
| Data / Monospace | **IBM Plex Mono** | Used for audit log entries, certificate IDs, API keys — signals "this is a precise, verifiable record" |

Type scale: 12 / 14 / 16 / 20 / 24 / 32 / 40px, with 16px as the body baseline (never smaller for body text, per WCAG AA readability).

### 2.3 Spacing

8px base grid (`--cyb-space-1` = 8px through `--cyb-space-8` = 64px). Bootstrap 5's default spacing utilities are remapped to this scale via SCSS variable overrides rather than fighting Bootstrap's defaults.

### 2.4 Icons

Bootstrap Icons as the base set (consistency with framework, no extra dependency), supplemented with a small custom icon set for domain-specific concepts Bootstrap Icons doesn't cover well (Risk Ring glyph, Phishing Simulation icon, Audit Evidence Package icon).

### 2.5 Colour Modes

Both Light and Dark modes are first-class, not a Dark-mode-as-inverted-filter afterthought. Mode preference is user-level (not tenant-forced), stored per-user, defaulting to system preference (`prefers-color-scheme`) on first login.

---

## 3. Navigation

- **Primary navigation:** persistent left sidebar (collapsible), consistent across all authenticated views — matches the mental model of Azure Portal / Atlassian rather than top-nav-only patterns, since users will live in this tool across long sessions.
- **Top bar:** organisation switcher (for users with multi-tenant access, e.g. consultants), notifications bell, user menu, mode toggle (Light/Dark).
- **Role-aware navigation:** sidebar items are filtered by RBAC — an Employee never sees "Audit Centre" or "Billing" in their nav, rather than seeing it greyed out. Reduces cognitive load and avoids implying access that doesn't exist.

---

## 4. Dashboard Layouts

### 4.1 Executive Dashboard
Hero: large Risk Ring showing organisation-wide Human Risk Score with trend arrow, flanked by an AI-generated plain-language narrative ("Your organisation's risk trend improved 8% this quarter, driven primarily by reduced phishing susceptibility in Finance."). Below: department heatmap (Risk Ring per department, colour-coded), compliance completion percentage, recent incidents summary.

### 4.2 Compliance Dashboard
Table-dense view: filterable by department/course/policy, completion status columns, overdue flags in red, one-click export button prominent in the top-right (this is the highest-frequency action for this persona, per Phase 3 user flows).

### 4.3 Employee Home
Minimal, task-focused: "Your assigned training" list with progress bars, upcoming deadlines, a single prominent "Report Phishing" button always accessible (per FR-5.4, this needs to be fast to find in a real suspicious-email moment, not buried in a menu).

---

## 5. Wireframes

High-fidelity wireframes build directly on the structural layouts approved in Phase 3 Section 9 (Executive Dashboard, Course Player, Audit Centre), applying the token system above. Additional high-fidelity screens for this phase: Login/MFA flow, Course Builder (drag-drop lesson sequencing), Phishing Campaign Builder, Incident Reporting form, Policy Acknowledgement modal.

*(Pixel-level mockups to be produced as Figma/design-tool artifacts outside this document; this guide defines the system those mockups must conform to.)*

---

## 6. Mobile Design

- Responsive breakpoints follow Bootstrap 5 defaults (`sm` 576px, `md` 768px, `lg` 992px, `xl` 1200px).
- Sidebar collapses to a bottom tab bar below `md` for the Employee persona's most common actions (Home, Training, Report Phishing, Notifications) — Admin/Compliance/Audit-heavy screens are usable but not optimised for mobile-first, since that persona's core tasks (bulk data review, evidence export) are genuinely desktop-appropriate work.
- Course Player is fully mobile-optimised end-to-end, per SRS US-2 acceptance criteria.

## 7. Desktop Design

- Primary design target; dashboards assume a minimum 1280px viewport for full layout, with graceful reflow down to 992px before mobile patterns take over.
- Data-dense screens (Compliance Dashboard, Audit Centre, Course Builder) are desktop-first by nature of the work being done on them.

## 8. Accessibility

- WCAG 2.1 AA baseline across all screens (per Phase 2/3 NFR).
- All interactive elements keyboard-navigable with visible focus states (not suppressed via `outline: none` without a replacement).
- Colour is never the sole indicator of risk state — Risk Ring and status badges always pair colour with a label or icon (e.g. "High Risk" text, not just red).
- Reduced-motion preference respected — Risk Ring animations (fill-on-load) disabled in favour of instant state for users with `prefers-reduced-motion`.
- Form errors are specific and instructive ("Password must include a number" not "Invalid input"), consistent with the interface voice principles below.

## 9. Interface Voice

- Plain, active language: "Save changes," not "Submit." Button labels match the confirmation that follows ("Publish" → "Published").
- Errors state what happened and how to fix it, without apologising or being vague.
- Empty states are invitations to act ("No courses assigned yet — browse the catalogue" rather than a bare "No data").
- Terminology is consistent platform-wide: "Human Risk Score" is never referred to as "risk rating," "risk index," or any synonym elsewhere in the product — one concept, one name, everywhere.

---

## Deliverables

- This UI Design Guide (design system, navigation, dashboard layouts, wireframe direction, mobile/desktop rules, accessibility standards, interface voice)

## Assumptions

- Bootstrap 5's utility classes and grid system can be sufficiently customised via SCSS variable overrides to achieve this design system without needing to eject from Bootstrap entirely.
- High-fidelity pixel mockups will be produced in a design tool outside this document by whoever owns visual design execution; this guide is the binding specification those mockups must satisfy, not a replacement for them.

## Risks

- Serving two distinct personas (data-dense compliance professional vs. minimal-friction employee) from one design system risks the dashboard-heavy screens creeping complexity into the employee-facing screens if not actively guarded in design review — recommend a lightweight design review checklist in Phase 11 that explicitly checks each new employee-facing screen against the "minimal, task-focused" principle before merge.
- Risk-tier colour contrast (amber/red/green) must be re-verified once actual Dark Mode surface colours are finalised in implementation — token values above are a starting point, not final-tested contrast ratios.

## Next Phase

**Phase 7 — Security Architecture:** Threat model, OWASP Top 10 mitigations, authentication/MFA/password policy detail, encryption, secure session management, audit logging, backups, security headers, rate limiting, and specific defences (CSRF, XSS, SQL injection, file upload security).
