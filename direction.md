# Cash Flow Direction Invariant

## Problem
`StoreCashFlowRequest` accepted `type: 'adjustment'` with no `direction` rule. The database
CHECK `cash_flows_adjustment_requires_direction` rejects such a row, so routing
`CashFlowController::store` would have answered **500** instead of a clean 422.
`store` and `update` were unrouted, which was the only reason it was harmless.

Separately, a row that predated the requirement could only be repaired by hand: the
executive dashboard warns that unsigned adjustments are excluded from the cash balance and
that an administrator must mark each one, and the only mechanism was the
`duukaflow:finance:unsigned-adjustments` artisan command.

## Task
Give the rule "an adjustment must have a direction" one owner, close the 500, and make the
existing dashboard warning actionable. Read rules.md.

## Root cause
One rule, enforced in two places — the adjustment endpoint's request and the DB CHECK —
with the six copies of the legal values written as literals and no shared definition.
Both findings fell out of that single weakness rather than being two bugs.

## Status — done

### What changed

**Enums.** `app/Enums/CashFlowType.php` and `CashFlowDirection.php`. The sign mapping moved
onto `CashFlowType::sign()`, with `Adjustment` resolving to `null` — it has no type to infer a
sign from, which is the entire reason the invariant exists. `CashFlow::cashEffect()` and
`FinanceService`'s SQL CASE both read from the enum cases, so the PHP and SQL readings of the
rule can no longer drift without a type changing.

**The rule.** `app/Rules/RequiresDirectionForAdjustments.php`, called from both write
requests via `withValidator()`.

**Routing.** `POST /api/finances` and `PATCH /api/finances/{cashFlow}`, behind
`canManageSensitiveFinance`. `destroy` stays unrouted: deleting a ledger row can strand the
sale it documents, and reversing stock on deletion is §3's open schema decision.

**The repair path.** `PATCH /api/finances/adjustments/{cashFlow}/direction`, plus a
`DirectionControl` in `FinanceTransactionTable` and a rewording of the warning in
`FinanceSummaryCards`.

**Also fixed:** `isOutflow()` in `FinanceTransactionTable` keyed off `type` alone, so every
adjustment rendered with a green `+` regardless of which way the money moved — a display bug
sitting directly on this invariant. `UpdateCashFlowRequest`'s `Rule::unique` was unscoped by
tenant, so a collision would have named another business's transaction code in a 422.

### Two judgement calls worth review

**Routing `store` needed more than a validation rule.** Taking `type` from the payload makes
a generic ledger write a revenue forgery endpoint, because `FinanceService::dashboard()`
totals `gross_revenue` straight off that column. The request therefore accepts only
`adjustment`, generates the transaction code server-side, and prohibits `sale_id`,
`purchase_id`, `customer_id`, `supplier_id` and the other event links. The other six types
stay owned by `CashFlowService`, which writes them when the sale, purchase or return happens.
A real need for a hand-written ledger row should arrive as its own endpoint with its own
decision about `type`, not as a widening of this one.

**A missing `direction` on update means "leave it alone", not "error".** Strict validation
froze legacy rows: you could not edit the description of a signless adjustment until you
repaired it, which is a hole that cannot be mentioned. Only an explicit `null` on a row that
is *currently* signed is refused, because that is the one payload that opens a new hole.
Branch this in `validatePayload` if the stricter reading is preferred.

### Tests
`api/tests/Feature/Finance/CashFlowDirectionInvariantTest.php` — 24 tests. The 422 that was a
500; the forgery guard; server-generated transaction codes; the update rules; the repair
endpoint including its balance effect and its refusals; cross-tenant 404; and the role gates.
It reuses the `withoutDirectionConstraint()` helper from `UnsignedAdjustmentRecoveryTest` to
recreate the legacy rows the repair path exists for.

`ui/src/app/pages/dashboards/executive/components/finance/FinanceTransactionTable.test.tsx`
— 7 tests: the control appears only on an unsigned adjustment, sends the chosen direction,
and an unsigned adjustment is drawn as neither an inflow nor an outflow.

### Verification
- `docker compose exec -T backend php artisan test` → **757 passed**, plus the 4 pre-existing
  failures below
- `docker compose exec -T backend ./vendor/bin/pint --test` → 801 files, clean
- `cd ui && npx vitest run` → **756 passed** across 11 files
- `npx eslint` on the four files touched → clean
- `npx tsc -b --noEmit` → no errors in the changed sources

### Not fixed — pre-existing, unrelated
`BranchPerformanceReportsTest` fails 4 tests. Its fixture date is `2026-09-05` and the
`last_30_days` window is `now()->subDays(30)`, which on 2026-10-06 opens on 2026-09-06. The
fixture is one day outside it. This is checked.md §2's time-dependent test, excluded from
this task by scope. Verified pre-existing by running the file against `3c550ba` in a
scratch worktree, and by inspection: this change does not touch that test, that service, or
the report query.

### Also noticed, not changed
- `CashFlowFactory::definition()` is empty; three report tests hand-roll every column.
- `payment_status_id` is passed by `CashFlowService:42` and `:65` but is absent from
  `CashFlow::$fillable`, so it is silently dropped on those two writes.
- `afroduuka_inventory.dump` predates both the direction CHECK and the `running_balance`
  removal. Restoring it yields a schema that fails
  `FinanceCashBalanceTest::test_the_column_is_gone` and carries no invariant.
