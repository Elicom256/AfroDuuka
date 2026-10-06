# DuukaFlow — Open Findings

**Source:** the pre-launch review of 2026-10-01 @ `b718ad0`, re-verified against `oct`.
**Trimmed:** 2026-10-06 — completed items removed. What remains is what is actually undone.

**State:** P0 resolved · P1 complete · P2 partly done. Dev runs against local Postgres.
Suite green at **737 backend / 747 frontend**, except the time-bomb below.

---

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

## 2. Latent traps in code that is unreachable today

- ⬜ **`StoreCashFlowRequest` allows `type: 'adjustment'` with no `direction` rule.** The
  database now rejects a directionless adjustment, so the day anyone routes a POST to
  `CashFlowController::store` it returns **500** instead of a clean 422. `store` and
  `update` are unrouted today, which is the only reason this is harmless.
- ⬜ **No endpoint can set a direction** on a legacy unsigned adjustment. The dashboard
  warning tells the user an administrator must act, and today that means the
  `duukaflow:finance:unsigned-adjustments` command.
- ⬜ **One time-dependent test.** `BranchPerformanceReportsTest` hardcodes a fixture date
  of `2026-09-05` inside a `last_30_days` window. On 6 October the window opened on
  7 September, the fixture fell outside it, and four tests failed for reasons unrelated to
  the code. Worth a sweep for other hardcoded dates.
- ⬜ **One intermittent test.** `test_shrinking_a_return_gives_the_revenue_back` failed
  once in a full run and passed on rerun and on the next full run. Not chased.

## 3. Data integrity

- ⬜ **`stock_movements.reference_type/id` are unconstrained polymorphic strings.** Deleting
  a sale orphans its movements and never reverses stock. This is a **schema decision, not a
  code fix** — real foreign keys per reference type, or a reversal step on sale deletion.
  Both change behaviour.
- ⬜ **Jobs never wrap work in `BusinessContext::run()`.** Not a current defect: all five
  jobs filter on `business_id` explicitly in every query. It is a footgun for future jobs,
  and wrapping them is a defensive refactor that wants its own focused pass.

## 4. Launch readiness

- ⬜ **No backup restore drill.** `DatabaseBackup.php` exists with no schedule, no
  off-site copy and no tested restore. An untested backup is not a backup.
- ⬜ **No adversarial multi-tenant UAT.** Given the P0 history — tenants able to ban each
  other, cross-tenant reads — this should be a deliberate attempt to read another tenant's
  data with two tenants seeded, not a happy-path walkthrough.
- ⬜ **URA e-invoicing retry is unverified.** A duplicate fiscalisation number is a
  compliance event, not a UI glitch.
- ⬜ **No error tracking or uptime alerting.** `/api/health` now answers correctly and
  nothing is watching it. Outages will surface through customers.
- ⬜ **No payments.** ~90% of Ugandan retail is mobile money; a cash-only POS with manual
  confirmation cannot be sold as a production system. The largest commercial gap here, and
  it needs a provider account and a decision rather than a patch.

## 5. Quality — the rest of P2

- ⬜ **No route-level code splitting.** The whole bundle ships as one chunk.
- ⬜ **178 hardcoded `any`.** Not swept on purpose. The right lever is a lint budget
  (`no-explicit-any` as a warning with a ratchet), which is a tooling decision; 178 file
  edits would risk working screens for no runtime gain.
- ⬜ **Accessibility sweep.** 57 of 61 tables lack `overflow-x-auto`; 119 `<Input>` lack
  `id`; 181 `<Label>` lack `htmlFor`; hand-rolled dialogs have no `role="dialog"` or focus
  trap. Large and mechanical, not started.
- ⬜ **POS is desktop-only.** A design change rather than a bug fix.

## 6. Product decisions taken, not work outstanding

- **Supplier, customer and editor roles stay seeded** with no dashboard. A business needs
  to record the people and companies it buys from and sells to; the missing piece is the
  portal UI, not the role. Building those portals is the follow-up when there is appetite.
- **`staff` was deleted**, not seeded. Every person in a business is staff, so a separate
  role split one job across two vocabularies and its tree was unreachable anyway.

## 7. Deliberately not fixed by design (from the review's §9)

These need a decision or a provider, not engineering:

1. WhatsApp still runs on the `demo` provider by default, and the catalogue is not yet
   dispatched from real business events, so nothing actually sends.
2. Receipts exist as PDFs and reach nobody. No email or SMS delivery.
3. No subscription auto-collection, so SaaS revenue depends on manual follow-up.
4. POS is not offline-capable. Retrofitting sync after data exists is far costlier.
