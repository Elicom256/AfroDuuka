# Test Fixture Faults

## Problem
Two §2 findings, both reported as test problems and neither of them one:

1. `BranchPerformanceReportsTest` had 4 failing tests.
2. `test_shrinking_a_return_gives_the_revenue_back` failed once in a full run, passed on
   rerun, and passed on the next full run.

## Task
Find out why each was failing and fix it at the root. Read rules.md.

## Status — done

Both were fixtures depending on something that moves. Neither was a defect in the code
under test, and in both cases the test suite was reporting a fixture fault in a way that
pointed at the arithmetic instead.

## 1. The time-dependent test

`BranchPerformanceReportsTest` asks the endpoint for `period=last_30_days` and dated its
fixture rows with a literal `2026-09-05`. `AnalyticsTrendHelper::resolvePeriodWindow()`
resolves that period as `now()->subDays(29)->startOfDay()` to `now()->endOfDay()` — a window
that moves with the calendar. On 6 October it opened on 2026-09-06, the fixture fell one day
outside it, and 4 tests began failing with no code change at all. The failure mode is an
empty branch list, which reads as a broken query rather than a stale date.

The fix pins the window rather than unpinning the fixture, because the fixture is what makes
the test readable — `2026-09-05` says "somewhere in the period" at a glance, where
`now()->subDays(9)` does not. An absolute date paired with a moving window is the trap, so
the moving half gets frozen:

- `Carbon::setTestNow()` in `setUp`, cleared in `tearDown`. setTestNow is global state that
  outlives the test if left in place, and a later file trusting the real clock would read a
  frozen one instead.
- The fixture date is now `self::IN_PERIOD`, and `self::NOW` sits on one named constant so
  the pair is read together.
- `test_the_fixture_date_sits_inside_the_requested_period` asserts the fixture really is
  inside the window the file's own clock produces, and reports both bounds in its failure
  message. This guards the guard: the original bug produced an empty result, which pointed
  nowhere near the calendar.

`test_it_excludes_cash_flows_outside_the_period` had its own local `setTestNow`; that is now
the `setUp` one, so the three cases it builds are read against a known window.

### Swept the rest

Every other hardcoded date in `tests/` was checked against a period filter. They are safe:
`MonthlyPerformanceReportTest`, `MonthlyPerformanceBranchScopeTest` and `TaxPaymentTest` all
pass `?month=` or compare against explicit dates, so their windows are pinned by the request
rather than by `now()`. The only two files combining a relative period with a literal
fixture were `BranchPerformanceReportsTest` (fixed) and this one.

## 2. The intermittent test

`ProductFactory` draws `quantity` from `fake()->numberBetween(0, 100)`. Shrinking or deleting
a return takes the units back out through `InventoryService::stockOut()`, which refuses to
take stock below zero. So when `completedSaleOfPhones()` happened to draw a product at 0,
`handleUpdateSaleReturn()` threw `Insufficient stock for product: phone-a` — about a 1-in-100
chance per product — before reaching any of the revenue assertions the test exists to check.

That is why it looked like a flake in the return arithmetic: the exception arrived from a
service the test does not name, and the assertion that never ran was the one under suspicion.

Confirmed by direct experiment rather than inspection. Seeding stock at 0 explicitly
reproduces the throw; seeding at 1, 2, 50 and 100 all pass. So the boundary is exactly the
zero stock, and nothing else about the test is time- or order-dependent. Two other hypotheses
were tested and ruled out first: a `created_at` landing on the last second of a day
(`endOfDay()` resolving to `23:59:59`), and `Carbon::setTestNow()` leaking between test
files — Laravel resets it per test, so neither holds.

`completedSaleOfPhones()` now seeds `quantity` explicitly, and
`test_the_sale_fixture_seeds_enough_stock_to_take_a_return_back` asserts it. The point of the
assertion is that the fix cannot silently regress to the random draw and turn the suite
intermittently red again for an unrelated reason.

### Swept the rest

Scanned every `Product::factory()` in `tests/` for a call that reaches a stock-moving path
without seeding `quantity`. Three remain, and all three are safe: two in
`MutatingEndpointAuthorizationTest` POST a purchase, which increments stock rather than
decrementing it, and one in `TenantIsolationTest` asserts a 409, so the sale is refused
before any stock moves. The negative-stock guard only exists in `InventoryService::stockOut()`,
which only returns and write-offs reach — `SaleItemService` decrements without a guard, so
a sale cannot fail this way.

## Tests
- `BranchPerformanceReportsTest` — 9 passing (was 8 with 4 failing). One added.
- `SaleReturnRevenueReconciliationTest` — 26 passing (was 25). One added.
- Full backend suite — **763 passed**, 2249 assertions, run five times consecutively to
  confirm nothing is left intermittent.
- `pint --test` — 801 files, clean.

## Verification
```
docker compose exec -T backend php artisan test            → 763 passed, ×5 runs
docker compose exec -T backend ./vendor/bin/pint --test    → 801 files, PASS
cd ui && npx vitest run                                   → 756 passed (untouched)
```

No production code was changed. That is the finding: both failures were in the fixtures, and
both fixtures were reaching for something random or something that moves.
