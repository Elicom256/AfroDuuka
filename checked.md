# DuukaFlow — Open Findings

**Source:** the pre-launch review of 2026-10-01 @ `b718ad0`, re-verified against `oct`.
**Trimmed:** 2026-10-06 — completed items removed. What remains is what is actually undone.

**State:** P0 resolved · P1 complete · P2 partly done. Dev runs against local Postgres.
Suite green at **774 backend / 756 frontend**. §2 closed; §3 has one item left and it needs a
schema decision. §1 is parked — Neon is no longer wanted.

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

- ✅ **The direction invariant now has one owner.** `CashFlowType` and `CashFlowDirection`
  enums replace the six literal copies of the allowed values, and
  `RequiresDirectionForAdjustments` states the rule once for both write requests. The 500
  is gone: `POST /api/finances` with `type: 'adjustment'` and no `direction` is a 422
  naming the field. `store` and `update` are now routed behind
  `canManageSensitiveFinance`, with `type` restricted to `adjustment` — the other six types
  stay owned by `CashFlowService`, since taking `type` from a client would have been a
  revenue forgery endpoint. `type` is immutable on update.
- ✅ **A legacy unsigned adjustment can be repaired from the dashboard.**
  `PATCH /finances/adjustments/{id}/direction`, gated by the same role check that guards
  creating one. Its refusals and the `duukaflow:finance:unsigned-adjustments` command's come
  from one `UnsignedAdjustmentResolver`, so the endpoint and the shell cannot disagree; the
  command keeps its cross-tenant reach. The transactions table offers the two directions on
  each unsigned row, and the warning copy now points there.
- ✅ **The time-dependent test.** `BranchPerformanceReportsTest` asked for
  `period=last_30_days` while dating its fixture with a literal, so the two drifted apart:
  the window is `now()->subDays(30)` and moved past the fixed `2026-09-05`, and 4 tests
  began failing with no code change. The clock is now frozen in `setUp` and cleared in
  `tearDown`, with a test asserting the fixture is inside the window the file's own clock
  produces — the fixture is an absolute date again, and the window is the pinned thing.
- ✅ **The intermittent test.** `test_shrinking_a_return_gives_the_revenue_back` failed once
  in a full run and passed on every rerun. It was `ProductFactory`, which draws
  `quantity` from `numberBetween(0, 100)`: rewriting a return takes the units back out
  through `InventoryService::stockOut`, which refuses to go below zero, so a product drawn at
  0 threw `Insufficient stock` about 1 run in 100 before reaching the revenue assertions.
  `completedSaleOfPhones()` seeds stock explicitly, and a test now asserts it, so the
  fixture cannot silently regress to the random draw. Confirmed by seeding stock at 0
  explicitly and reproducing the throw, then at 1/2/50/100 and seeing it pass.

## 3. Data integrity

- ⬜ **`stock_movements.reference_type/id` are unconstrained polymorphic strings.** Deleting
  a sale orphans its movements and never reverses stock. This is a **schema decision, not a
  code fix** — real foreign keys per reference type, or a reversal step on sale deletion.
  Both change behaviour.
- ✅ **Jobs now run inside their tenant.** All five were audited and the shapes turned out
  to differ, so one blanket wrap would have broken two of them:
  - `CheckNotificationsJob` and `ProcessSubscriptionLifecycleWhatsAppJob` are **platform
    sweeps** — they iterate every business on the install. The wrap goes *inside* the loop,
    per row. A single wrap around `handle()` would scope each sweep to whichever tenant was
    entered first and silently stop alerting the rest. That failure mode is now pinned by a
    test rather than left to be discovered.
  - `ProcessWhatsAppNotificationJob` and `SendNotificationJob` target **one business** each,
    taken from the payload and from the delivery row respectively. One wrap each.
  - `ProcessSesSuppressionsJob` is **deliberately cross-tenant** and is left that way. It is
    the backstop for sends the provider never confirmed, and narrowing it would settle one
    tenant's stranded rows while reporting success. The class now says so, and says what to
    do instead if it ever gains a per-row write.
  Every query already named `business_id`, so this is not a fix — it is what makes the next
  query correct by construction. The sharpest case is `Product`, which carries no
  `business_id` column at all and relies entirely on the branch scope, which applies no
  constraint without a context.
- ⬜ **The scheduler may not be running.** `CheckNotificationsJob`,
  `ProcessSubscriptionLifecycleWhatsAppJob` and `ProcessSesSuppressionsJob` are on
  `routes/console.php`, but there is no `schedule:run` cron and no scheduler container in
  either compose file — the only cron in `ops/` is the database backup. So all three
  scheduled jobs may simply never fire in the deployed environment. Worth confirming before
  anything else in this section is worth building on.

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
