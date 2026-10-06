# DuukaFlow — Open Findings

**Source:** the pre-launch review of 2026-10-01 @ `b718ad0`, re-verified against `oct`.
**Trimmed:** 2026-10-06 — completed items removed. What remains is what is actually undone.

**State:** P0 resolved · P1 complete · P2 complete. Dev runs against local Postgres.
Suite green at **774 backend / 756 frontend**. §1 is parked — Neon is no longer wanted.
What is left is launch readiness, plus the product and design decisions recorded below.

---

# Tasks:

## 1. Production database — decide this before anything else

- ⬜ **Neon `neondb` was dropped.** `php artisan migrate:fresh` was run from inside the
  backend container while `DB_URL` still pointed at Neon, so every table went. Whether it
  holds anything is the question that matters; if it did, restore via Neon point-in-time
  recovery to a moment before the drop. The app no longer connects to Neon at all, so this
  is now purely a decision about whether to restore.
- ⬜ **Nothing stops a destructive command hitting production again.** The immediate cause
  is fixed — `DB_URL` is out of `api/.env` and the container was recreated, so dev runs
  against local Postgres. But `migrate:fresh` inside that container would again follow
  whatever `DB_URL` says, and nothing warns. `phpunit.xml` forces `DB_URL=""` for the test
  suite only; a hand-run artisan command has no such guard.
- ⬜ **The Neon credential should be rotated.** A live production connection string was
  sitting in the dev container's environment, and has been printed in this session's
  output. Rotate it, and keep production credentials out of any dev environment.
- ⬜ **Migrations have never been verified against Neon.** All 90 apply cleanly to local
  Postgres 17, but against Neon the very first migration aborted with
  `current transaction is aborted`. `create_cache_table` compiles
  `$table->string('key')->primary()` into `alter table "cache" add primary key`, and that
  is where it died. Until this runs green against the real target, a production deploy is
  a gamble.

## 2. Launch readiness

- (a) ⬜ **No backup restore drill.** `DatabaseBackup.php` exists with no schedule, no
  off-site copy and no tested restore. An untested backup is not a backup.
- (b) ⬜ **No adversarial multi-tenant UAT.** Given the P0 history — tenants able to ban each
  other, cross-tenant reads — this should be a deliberate attempt to read another tenant's
  data with two tenants seeded, not a happy-path walkthrough.
- (c) ⬜ **URA e-invoicing retry is unverified.** A duplicate fiscalisation number is a
  compliance event, not a UI glitch.
- (d) ⬜ **No error tracking or uptime alerting.** `/api/health` now answers correctly and
  nothing is watching it. Outages will surface through customers.
- (e) ⬜ **No payments.** ~90% of Ugandan retail is mobile money; a cash-only POS with manual
  confirmation cannot be sold as a production system. The largest commercial gap here, and
  it needs a provider account and a decision rather than a patch.

## 3. Product decisions taken, not work outstanding

- (a) **Supplier, customer and editor roles stay seeded** with no dashboard. A business needs
  to record the people and companies it buys from and sells to; the missing piece is the
  portal UI, not the role. Building those portals is the follow-up when there is appetite.
- (b) **`staff` was deleted**, not seeded. Every person in a business is staff, so a separate
  role split one job across two vocabularies and its tree was unreachable anyway.

## 4. Deliberately not fixed by design (from the review's §9)

These need a decision or a provider, not engineering:

1. WhatsApp still runs on the `demo` provider by default, and the catalogue is not yet
   dispatched from real business events, so nothing actually sends.
2. Receipts exist as PDFs and reach nobody. No email or SMS delivery.
3. No subscription auto-collection, so SaaS revenue depends on manual follow-up.
4. POS is not offline-capable. Retrofitting sync after data exists is far costlier.
