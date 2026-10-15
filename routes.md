# Dashboard routes that "failed to fetch"

## Symptom

Moving between dashboard pages surfaced data-loading errors on several cards
("Unable to load top products", "Low-stock alerts unavailable", etc.) and, in
some cases, the generic middleware toast. The pages themselves rendered; only the
data behind certain cards was missing.

## Root cause

`api/bootstrap/app.php` registered the middleware alias

```php
'feature' => RequireFeature::class,
```

but never imported `App\Http\Middleware\RequireFeature`. The file has no
namespace, so `RequireFeature::class` did **not** resolve to the fully-qualified
class name — it compiled to the literal string `"RequireFeature"`. Every route
guarded by `feature:*` therefore failed to build its middleware stack:

```
Illuminate\Contracts\Container\BindingResolutionException:
Target class [RequireFeature] does not exist.
```

That is a **500** on every feature-gated endpoint — reports, promotions,
suppliers, customers and attendance — regardless of whether the feature was on
or off. The dashboard cards that call those endpoints turned the 500 into their
error state.

The alias was introduced in commit `0e786e8` ("feat(feature-flags)…"), which
added the gate but forgot the import. The gate had never actually run, which is
also why the pre-existing feature-flag tests were failing at HEAD.

## Fix

`api/bootstrap/app.php`:

```php
use App\Http\Middleware\RequireFeature;
```

One import. With it, the alias resolves and the gate runs as designed.

## Post-fix behaviour

The gate now answers correctly instead of 500-ing. It is an *opt-in* switch per
business (`api/config/features.php`), read most-specific-first by
`App\Support\Features\FeatureFlags`: an explicit settings row wins, then the
business's plan `features` list, then the catalogue default.

| Feature      | Default | Middleware guard         | Routes |
|--------------|---------|--------------------------|--------|
| `reports`    | off     | `feature:reports`        | `GET api/reports/{branch-performance, monthly-performance, monthly-performance/pdf, stock-summary, low-stock, out-of-stock, dead-stock, inventory-valuation, sales-by-product, stock-movement}` |
| `promotions` | off     | `feature:promotions`     | `api/promotions` (index, store, show, update, destroy) |
| `attendance` | off     | `feature:attendance`     | `api/attendances` (resource) |
| `customers`  | on      | `feature:customers`      | `api/customers` + customer attachments |
| `suppliers`  | on      | `feature:suppliers`      | `api/suppliers` + supplier attachments |

When a gated feature is off, the request now returns:

```json
403 {"message":"Reports is not enabled for your business. An administrator can turn it on in Settings.","code":"feature_disabled","feature":"reports"}
```

New businesses are seeded with reports *disabled* by
`App\Services\CoreBusinessSettings`, so a fresh dashboard legitimately sees the
`reports` cards locked rather than populated.

## Frontend: lock the cards instead of erroring

Because a disabled feature is an expected state (not a failure), the dashboard
cards backed by `reports` endpoints are now gated in the UI rather than left to
render a 403 as an error.

- New `ui/src/app/components/FeatureLockedCard.tsx` — keeps the card frame and
  title so the grid does not reflow, and explains the state in place of the
  fetch error.
- `useFeatureSettings()` (`ui/src/app/hooks/useFeatureSettings.ts`) is the single
  source of the `reports` flag plus a shared `loading` flag.
- While the flag is still loading, each card shows its normal skeleton.
- While the flag is off, each card renders `FeatureLockedCard` and its queries are
  skipped via RTK Query's `{ skip: true }`, so **nothing is fired** and no
  deliberate 403 ever reaches the UI.

Cards gated:

| Component | Queries skipped when reports is off |
|-----------|-------------------------------------|
| `executive/…/overview/TopProducts.tsx` | `useSalesByProductQuery` |
| `executive/…/stock-health/StockHealth.tsx` | `useLowStockQuery`, `useOutOfStockQuery`, `useProductExpiringQuery` |
| `Operations/…/stock-alerts/OperationsStockAlerts.tsx` | `useLowStockQuery`, `useOutOfStockQuery`, `useProductExpiringQuery` |
| `Operations/pages/OperationsAnalyticsPage.tsx` | `useLowStockQuery`, `useOutOfStockQuery`, `useStockSummaryQuery`, `useInventoryValuationQuery` |

Non-reports analytics on `OperationsAnalyticsPage` (product analytics,
restocking) stay live.

## Tests

- `api/tests/Feature/Reports/*` now enable `ReportsSettings` for the business
  under test, since the routes they exercise are gated.
- `api/tests/Feature/MutatingEndpointAuthorizationTest.php` enables
  `PromotionsSettings` (it tests authorization on the promotion routes) and
  asserts the branch-manager coupon delete as a **soft** delete
  (`assertSoftDeleted`) — `Coupon` uses `SoftDeletes`, so the row correctly
  remains with `deleted_at` set.
- `api/tests/Feature/RequireRoleMiddlewareTest.php` enables `ReportsSettings`,
  because it treats the branch-scoped reports endpoint as an open floor route.
- `api/tests/Feature/MutatingRouteAuthorizationAuditTest.php` documents the
  public chatbot proxy and email-resend routes in `INTENTIONAL`.

## Verification

Baseline at HEAD: `70 failed, 898 passed` (the feature-gate 500s accounted for
most of them). After the fix: `17 failed, 951 passed`.

The 17 remaining failures are **pre-existing and unrelated** to this fix — they
fail identically at HEAD and cover stale/standalone test issues:

- `TenantIsolationTest` (7) — stale `QueryException`/`TypeError`/`BadMethodCallException`
- `ConcurrencyTest` (2), `Finance/SaleReturnRevenueReconciliationTest` (2)
- `QuotationTest` (2), `Tax\TaxCategoryTest`, `Tax\TaxRateTest`
- `Audit/ActivityLoggingOnMutationTest`
- `FrontendApiPathsTest` — scans a per-endpoint `baseUrl` pattern the frontend no
  longer uses after the RTK Query refactor, so it checks 0 paths.

UI: `npx tsc -b` clean, `npx vitest run` green (`todosRoutes.test.tsx` is a known
timing-flaky test that passes on re-run), eslint on the changed files reports 0
errors (only pre-existing `any` warnings).
