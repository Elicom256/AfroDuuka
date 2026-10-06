# DuukaFlow — Open Findings

**Source:** the pre-launch review of 2026-10-01 @ `b718ad0`, re-verified against `oct`.
**Trimmed:** 2026-10-06 — completed items removed. What remains is what is actually undone.

**State:** P0 resolved · P1 complete · P2 partly done. Dev runs against local Postgres.
Suite green at **774 backend / 756 frontend**. §2 closed; §3 has one item left and it needs a
schema decision. §1 is parked — Neon is no longer wanted.

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

- (a) ✅ **`stock_movements.reference_type/id` are unconstrained polymorphic strings.** The
  API now refuses to delete a sale at all, so a recorded stock movement cannot be orphaned
  by a deletion that never reverses inventory. The safer rule is enforced at the route and
  controller boundary: reverse the sale or issue a corrected return instead of deleting the
  source record.
- (b) ✅ **Jobs now run inside their tenant.** All five were audited and the shapes turned out
  to differ, so one blanket wrap would have broken two of them:
  - `CheckNotificationsJob` and `ProcessSubscriptionLifecycleWhatsAppJob` are **platform
    sweeps** — they iterate every business on the install. The wrap goes _inside_ the loop,
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
- (c) ✅ **The scheduler is now running.** `CheckNotificationsJob`,
  `ProcessSubscriptionLifecycleWhatsAppJob` and `ProcessSesSuppressionsJob` are scheduled in
  `routes/console.php`, and a dedicated scheduler service now runs
  `php artisan schedule:work` in both the dev and prod Docker stacks. There is no longer a
  silent gap where the cron entry was missing and nothing fired.

## 4. Launch readiness

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

## 5. Quality — the rest of P2

- (a) ✅ **Routes are code-split.** Every public page, every role tree and every page
  inside a tree now loads via `React.lazy`, with a `Suspense` boundary in `AppRoutes` and
  in each tree (so a tree mounted directly still resolves). The build emits a chunk per
  page instead of one bundle; the public site no longer ships dashboard code, and one
  role no longer downloads another's screens. The test setup waits longer for the first
  on-demand transform, which is a runner cost, not a slow render.
- (b) ✅ **931 hardcoded `any` now have a lint budget.** `no-explicit-any` is a warning
  (was an error), and `npm run lint` enforces `--max-warnings=931` — any new `any` fails
  the build. The ratchet only goes down.
- (c) ✅ **Accessibility sweep.** The table count was a false alarm: `Table` already wraps
  its `<table>` in `relative w-full overflow-x-auto`, so no callers needed a wrapper. All
  234 `<Input>` now carry an `id`, and 160 unpaired `<Label>`s gained `htmlFor`; every
  `htmlFor` in the repo resolves to an element with a matching `id` (18 pre-existing
  dangling ones — labels pointing at Radix `Select`s — were fixed by moving the id onto
  `SelectTrigger`, the pattern `AddSale` already used). The 8 hand-rolled overlays got
  `role="dialog"`, `aria-modal` and `aria-labelledby`, plus a new `useFocusTrap` hook that
  traps Tab, closes on Escape and restores focus to the trigger.
- (d) ✅ **POS works on mobile.** The page no longer pins itself to `h-screen`. Below
  `lg` the products/cart pane and the actions pane stack and the page scrolls, the cart
  keeps a usable min-height, the keyboard-shortcut hint is hidden (touch has no F-keys)
  and the customer/checkout modals size to the viewport instead of a fixed 400/500px.

## 6. Product decisions taken, not work outstanding

- (a) **Supplier, customer and editor roles stay seeded** with no dashboard. A business needs
  to record the people and companies it buys from and sells to; the missing piece is the
  portal UI, not the role. Building those portals is the follow-up when there is appetite.
- (b) **`staff` was deleted**, not seeded. Every person in a business is staff, so a separate
  role split one job across two vocabularies and its tree was unreachable anyway.

## 7. Deliberately not fixed by design (from the review's §9)

These need a decision or a provider, not engineering:

1. WhatsApp still runs on the `demo` provider by default, and the catalogue is not yet
   dispatched from real business events, so nothing actually sends.
2. Receipts exist as PDFs and reach nobody. No email or SMS delivery.
3. No subscription auto-collection, so SaaS revenue depends on manual follow-up.
4. POS is not offline-capable. Retrofitting sync after data exists is far costlier.
