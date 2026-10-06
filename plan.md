# Plan — cash flow direction invariant (checked.md §2, points 1 and 2)

Scope is the two findings in §2 that share one root cause. §2 points 3 (hardcoded fixture
date in `BranchPerformanceReportsTest`) and 4 (intermittent
`test_shrinking_a_return_gives_the_revenue_back`) are test-determinism problems with a
different cause and are **not** in this plan.

## The problem, stated as one thing

> `cash_flows` rows of `type = 'adjustment'` must have a non-null `direction`.

Today that rule is enforced in exactly two places, and everything else agrees with it only
by circumstance:

| Where | What it does |
|---|---|
| `StoreCashFlowAdjustmentRequest.php:48` | requires `direction` for the one adjustment endpoint |
| `create_cash_flows_table.php:80-83` | DB CHECK `cash_flows_adjustment_requires_direction` |

The remaining six copies of the allowed values are literals with no shared definition:
`StoreCashFlowRequest.php:59-62`, `UpdateCashFlowRequest.php:55-58`, the create migration
line 21, `FixUnsignedAdjustments.php:46`, and the type→sign mapping duplicated between
`CashFlow.php:172-176` and `FinanceService.php:92-98`.

From that one weakness both findings fall out:

- **§2.1** — `StoreCashFlowRequest` and `UpdateCashFlowRequest` both accept
  `type: 'adjustment'` while carrying no `direction` rule. That is precisely the payload
  the CHECK rejects, so the DB throws and the caller gets a 500 rather than a 422. Latent
  only because `CashFlowController::store`/`update` are unrouted.
- **§2.2** — a violation can only be repaired by someone with a shell. The dashboard
  warning at `FinanceSummaryCards.tsx:44-59` tells ordinary users "an administrator needs
  to mark each one", and the only mechanism is
  `duukaflow:finance:unsigned-adjustments`. Meanwhile `CashFlow::cashEffect()` returns
  `0.0` for a directionless adjustment, so the row is silently inert and `cash_balance`
  under-reports.

Note the failure is quiet by design (`ELSE 0` in the CASE at `FinanceService.php:97`, and
`default => 0.0` in `cashEffect()`), which is why a hole here produces a wrong number
rather than a visible error.

## Decisions taken

1. **Route `store` and `update` properly** — role-gated, with full direction validation.
2. **Add a role-gated repair endpoint** for a legacy unsigned adjustment, so the existing
   dashboard warning becomes actionable. The artisan command stays for cross-tenant bulk
   repair, sharing one validation path with the endpoint so the two cannot drift.
3. **Include the frontend action button** so the warning can actually be cleared from the
   dashboard.
4. **Add `CashFlowType` + `CashFlowDirection` enums** in `app/Enums` and replace all six
   literal copies.

## Step 1 — `CashFlowType` and `CashFlowDirection` enums

New directory `api/app/Enums` (the repo has none today; nothing to conform to beyond
PSR-12 and the existing docblock style).

`CashFlowType: string` — `Sale`, `Purchase`, `Expense`, `PaymentIn`, `PaymentOut`,
`Refund`, `Adjustment`.

`CashFlowDirection: string` — `Credit`, `Debit`.

Carry the two behaviours that are currently open-coded, so they stop being copy-paste:

- `CashFlowType::sign()` / a `typeSign()` helper for the type→sign mapping, used by
  `CashFlow::cashEffect()`. `Adjustment` has no implied sign, which is the entire reason
  the invariant exists.
- `CashFlowType::values()` and `CashFlowDirection::values()` so the request rules and the
  artisan command read `Rule::enum(CashFlowType::class)` rather than restating a list.

`FinanceService::netCashMovement()` keeps its SQL CASE — it cannot call PHP — but the CASE
branches are built from the enum values rather than typed as literals, so adding a type
forces a look at both sign paths.

## Step 2 — one shared validation rule for the invariant

The invariant is currently stated in the request and again in the DB, with nothing in
between. Add a small rule object both cash-flow write requests use, so "an adjustment
needs a direction" is written once at the application layer.

- New `api/app/Rules/RequiresDirectionForAdjustments.php` (or an equivalent method on the
  enum, if that reads cleaner): when `type` is `Adjustment`, `direction` must be present
  and be a valid `CashFlowDirection`; when `type` is anything else, `direction` must be
  absent. Mirrors `StoreCashFlowAdjustmentRequest.php:47-48` wording and the CHECK's
  intent.
- `StoreCashFlowRequest`: add the rule plus a `'direction'` key of
  `['nullable', Rule::enum(CashFlowDirection::class)]`, so the failure is a clean 422 with
  the field named. Replace the `Rule::in([...])` type list with `Rule::enum(CashFlowType::class)`.
- `UpdateCashFlowRequest`: same rule, using `sometimes` semantics — an update that flips
  `type` to `adjustment` without supplying `direction` must 422.
- `StoreCashFlowAdjustmentRequest`: swap `Rule::in(['credit','debit'])` for
  `Rule::enum(CashFlowDirection::class)`. Behaviour unchanged.
- `CashFlow` model: cast `type` and `direction` to the enums. Add the enum to `$casts`
  alongside the existing `decimal:2` and `date` casts. Keep `cashEffect()` working with
  `match ($this->type)` — matched against enum cases now.

Two latent hazards in `UpdateCashFlowRequest` get fixed in the same edit, because they are
one line away from the code being touched:

- `Rule::unique('cash_flows')->ignore($cashFlowId)` at line 48 passes a route model key.
  It must resolve to the id, and it is unscoped by tenant — a uniqueness check that leaks
  another tenant's code into a 422 message. Scope it by `business_id`.
- `CashFlow::create()` is passed `$validated` directly, and `StoreCashFlowRequest` accepts
  `sale_id`/`purchase_id`/`customer_id`/`supplier_id` from the client. Combined with step 3
  below this is the forgery vector; see there.

## Step 3 — route `store` and `update`, gated

`api/routes/finances.php`, inside the existing `auth:sanctum` group:

```
Route::post('/', [CashFlowController::class, 'store']);
Route::patch('/{cashFlow}', [CashFlowController::class, 'update']);
```

`destroy` stays unrouted. Deleting a cash-flow row directly can strand the parent sale,
purchase or return it documents, and the CHECK constraint has no answer to that. Reversing
stock on deletion is `checked.md` §3's open schema decision, not something to smuggle in
here.

Authorization, in the requests' `authorize()` so a refusal is a 403 before validation runs —
the pattern `StoreCashFlowAdjustmentRequest.php:21-24` already established:

- `StoreCashFlowRequest::authorize()` is currently `Auth::check()`. That is too weak for a
  routed money write: any authenticated user, including Operations, could add rows to the
  ledger. Change to `RolePermissions::canManageSensitiveFinance($this->user())`, the same
  gate that guards `POST /finances/adjustments`.
- `UpdateCashFlowRequest::authorize()` gets the same gate.

**The forgery problem this creates, and the answer.** `store` takes `type` from the
payload, so a gated-but-generic writer can write `type: 'sale'` with an arbitrary amount,
inflating `total_revenue` and `gross_revenue` on the dashboard — figures computed by
`FinanceService::dashboard()` straight off `type`. A role gate alone does not fix that; it
only limits who can do it.

So the request constrains which types may be written directly:

- Allow only `Adjustment` (and, for `update`, an existing adjustment's fields). The other
  six types are all produced by domain events through `CashFlowService`
  (`createCashFlowForSale` :30, `createCashFlowForPurchase` :54,
  `createCashFlowForExpense` :188, and the transfer/return helpers). They are *derived*
  data — the ledger is a consequence of a sale, not an input to it. Letting a client author
  one creates a second source of revenue truth, which `rules.md` forbids outright
  ("Avoid duplicate revenue calculations from multiple sources").
- `sale_id`, `purchase_id`, `customer_id`, `supplier_id` come off the request's validated
  output for this reason. They exist to link a row to the event that caused it, and a
  hand-written row has no such event.
- `transaction_code` is generated server-side, matching the `'CF-ADJ-'.Str::ulid()` pattern
  at `FinanceController.php:164`. `StoreCashFlowRequest::prepareForValidation()` currently
  generates one only when the client omits it, and uses `rand()`; a client can therefore
  collide or squat a code. Always generate.
- `created_by` is already overwritten with `Auth::id()` at
  `StoreCashFlowRequest.php:31`, so no spoofing there. Keep it, and drop it from `rules()`.

With those, `POST /api/finances` is a typed manual-adjustment writer and the routed surface
matches what the UI actually offers. If a real need for a generic ledger write appears, it
should arrive as its own endpoint with its own decision about `type` — not as a widening
of this one.

Wrap `store`/`update` bodies without the broad `catch (\Exception $e)` pattern used in
`FinanceController`. That pattern is what turned a role refusal into a 422 with a
"Failed to create adjustment" message; `FinanceController::adjustment()` kept a catch for
legacy reasons and should not be copied. Let the framework render validation failures.

## Step 4 — endpoint for §2.2, so the warning is actionable

`api/routes/finances.php`, next to the existing adjustments route:

```
Route::patch('adjustments/{cashFlow}/direction', [CashFlowController::class, 'setDirection']);
```

New `UpdateCashFlowDirectionRequest`:

- `authorize()` → `RolePermissions::canManageSensitiveFinance($this->user())`.
- `direction` → `['required', Rule::enum(CashFlowDirection::class)]`.
- Branch containment closure, copied from `StoreCashFlowAdjustmentRequest.php:28-38`, so a
  BranchManager cannot repair a row outside their scope.

`CashFlowController::setDirection()`:

- Route-model-bind the cash flow, so the tenant scope on `BaseModel` (`business` and
  `branch` global scopes, `BaseModel.php:26-58`) makes another tenant's row a 404 before
  the method runs. The endpoint deliberately does **not** use `withoutGlobalScopes()`, the
  way `FixUnsignedAdjustments.php:71` does — cross-tenant repair stays a CLI-only power.
- Refuse with 422 when `type !== 'adjustment'`: a sale takes its direction from its type,
  so this is the same refusal the command makes at `FixUnsignedAdjustments.php:65-70` and
  the test that pins it at `UnsignedAdjustmentRecoveryTest.php:151-171`.
- Refuse with 422 when `direction` is already set, matching the command's "Nothing changed"
  path at line 72-75.
- Set the direction and return the row.

Put the shared refusals in one place both this and the command call, so the endpoint and
`php artisan` cannot answer the same question differently. A small
`App\Services\UnsignedAdjustmentResolver` (or a method on `CashFlowService`, which
`CashFlowController` already injects) holding "is this an unsigned adjustment?" and "apply
this direction", with the command and the controller as thin callers.

Keep the command's cross-tenant listing untouched. It is the only way to clear a backlog
across businesses, which is what an install-wide operator needs.

## Step 5 — frontend

New mutation in `cashFlowQuery.ts` (the cash-flow slice, since it targets `/finances`):

```ts
setCashFlowDirection: builder.mutation<any, { id: number; direction: string }>({
  query: ({ id, direction }) => ({ url: `/adjustments/${id}/direction`, method: 'PATCH', body: { direction } }),
  invalidatesTags: ['CashFlowAPI'],
}),
```

`invalidatesTags: ['CashFlowAPI']` so the ledger refetches. The `unsigned_adjustments`
count comes from `getFinanceDashboard` in `financeQuery.ts:16`, tagged `'FinanceAPI'` — so
invalidate that slice too, or the warning stays on screen after a successful repair.

In `FinanceTransactionTable.tsx`, add a per-row control on rows where
`type === 'adjustment' && !direction`:

- A ShadCN `AlertDialog` or `DropdownMenu` with the two `CashFlowDirection` values, using
  the existing `FinanceAdjustmentDialog` control vocabulary — "Money In (credit)" /
  "Money Out (debit)" — so the repair reads the same way as the create.
- Lucide icons per `rules.md`: `ArrowDownLeft` for credit, `ArrowUpRight` for debit.
- `toast` on success, matching `FinanceAdjustmentDialog.tsx:54`.
- The record type at `FinanceTransactionTable.tsx:11-24` has no `direction` field. Add it.
- Rows without a direction should also stop being coloured as an inflow. `isOutflow()` at
  line 53 keys off `type` alone, so an adjustment renders with a green `+` regardless of
  which way the money actually moved — a display bug that sits directly on top of this
  invariant. Use `record.direction` when present, falling back to the current `type`
  behaviour for the six derived types.
- The row has an `Eye` action link at line 131-137 and a 10-column header. Adding a control
  means widening `colSpan` on the empty state and the header count together.

Update `FinanceSummaryCards.tsx:51-56`. The copy currently says an administrator must act,
which after this change is true from the dashboard — but it should say the action is
available here rather than implying an off-screen manual step.

Also add `overflow-x-auto` on this table's wrapper. `checked.md` §5 records 57 of 61 tables
without it; this one gains a column in this change, so fix it here rather than widening a
later a11y sweep. Do not touch the other 56 in this task.

## Step 6 — tests

Backend, `api/tests/Feature/Finance/`:

Extend `AdjustmentDirectionConstraintTest.php` (already pins the schema-level rule) or add
`CashFlowDirectionValidationTest.php` for the request layer:

- `POST /api/finances` with `type: 'adjustment'` and no `direction` → **422**,
  `assertJsonValidationErrors('direction')`, and `assertDatabaseCount('cash_flows', 0)`.
  This is the §2.1 regression test: today it would be a 500.
- Same with `direction: 'sideways'` → 422 on `direction`.
- `PATCH /api/finances/{id}` flipping a sale to `type: 'adjustment'` with no direction → 422.
- `POST /api/finances` with `type: 'sale'` → 422. The forgery guard from step 3.
- `POST /api/finances` as an Operations user → 403, `assertDatabaseCount('cash_flows', 0)`.
  Follow `MutatingEndpointAuthorizationTest.php:1145-1203`, which already covers this shape
  for `POST /finances/adjustments`, including the assertion that a refused role leaves the
  table empty even with an invalid payload.
- The happy path: a gated `POST /api/finances` with `type: 'adjustment'` +
  `direction: 'credit'` → 201/200, row carries the direction, `cash_balance` moves.
- `PATCH /api/finances/adjustments/{id}/direction` → sets it, `cash_balance` follows,
  `unsigned_adjustments` drops to 0. The existing
  `UnsignedAdjustmentRecoveryTest.php:125-137` asserts exactly this through the command;
  the endpoint version is the same assertions over HTTP.
- The endpoint refuses a non-adjustment (422) and an already-signed row (422).
- Cross-tenant: second business, second user, `PATCH .../direction` on the first
  business's row → 404, direction still null. No adversarial walkthrough — just the
  binding.

Use the `withoutDirectionConstraint()` helper already in
`UnsignedAdjustmentRecoveryTest.php:53-58` to create legacy rows for the repair tests.
Dropping the CHECK inside a test is the established way to reproduce the rows this feature
exists to fix.

Frontend, new `FinanceTransactionTable.test.tsx` under
`ui/src/app/pages/dashboards/executive/components/finance/`:

- An unsigned adjustment row offers the credit/debit control; clicking one fires the
  mutation and invalidates both slices.
- A signed adjustment row does not.
- An adjustment with no direction is not rendered as a green inflow.

Follow `UserProfile.test.tsx`: `render` wrapped in `Provider` + `MemoryRouter`, `vi.mock`
for the RTK Query hooks, `userEvent` for clicks.

## Step 7 — verify

```bash
docker compose exec -T backend php artisan test
docker compose exec -T backend ./vendor/bin/pint --test
docker compose exec -T frontend npm run test
docker compose exec -T frontend npm run lint
```

Baseline to hold: **737 backend / 747 frontend**, per `checked.md:7`. Note that
`BranchPerformanceReportsTest` has four time-dependent failures independent of this work
(`checked.md:43-45`) — if they appear, they are not caused by these changes.

## Not in this plan

- `checked.md` §2 points 3 and 4 (test determinism).
- `cash_flows.running_balance` in `afroduuka_inventory.dump`. The dump predates both the
  column removal and the CHECK, so restoring it yields a schema that fails
  `FinanceCashBalanceTest.php:332-337` and carries no invariant. It predates the invariant
  and is stale regardless; flag it, do not fix it here.
- `CashFlowFactory::definition()` is empty and three report tests hand-roll every column
  to compensate. Filling it in would let those tests shorten, but it touches three files
  outside this fix.
- `payment_status_id` is passed by `CashFlowService.php:42` and `:65` but is absent from
  `CashFlow::$fillable`, so it is silently dropped on those two writes. Unrelated to
  direction; worth a separate look.
