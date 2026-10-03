# DuukaFlow — Remaining Work Review

**Reviewed:** 2026-10-03  
**Scope:** Core product and launch readiness: tenant access, POS/sales, inventory, finance, procurement, reporting, frontend workflows, testing, and operations.  
**Excluded:** WhatsApp/email messaging and notification delivery, URA integration, and external-provider/API integration work. Sales payments recorded inside the app remain in scope; payment-provider integrations do not.

## Summary

DuukaFlow has substantial operational breadth: multi-branch inventory, POS and non-POS sales, purchase orders, returns, finance, staff, reports, audit surfaces, and role permissions are implemented. This is not a missing-feature-count problem. The outstanding risk is that a small number of core invariants can still fail, and the release process does not yet demonstrate a recoverable, consistently verified production system.

**Readiness: not yet ready for production rollout.** Prove backup restoration and address the remaining operational/UI release gaps before handling real business data.

## P0 — Fix Before Launch (completed)

### ✅ 1. Duplicate product lines can oversell in non-POS sales — Fixed

The non-POS sale service now sums requested quantities by product and compares each total against its locked product row before writing the sale. It decrements stock once per product and records one movement with the aggregate quantity. This closes the duplicate-line overdraw and ledger undercount paths.

Evidence and regression coverage: [api/app/Services/SaleItemService.php](api/app/Services/SaleItemService.php) and [api/tests/Feature/TenantIsolationTest.php](api/tests/Feature/TenantIsolationTest.php).

**Verified:** Docker tests cover rejection without writes when duplicate lines exceed stock, and successful duplicate lines decrement stock once with one correctly aggregated movement. The focused suite passed alongside the standard non-POS checkout and discount tests (4 tests, 19 assertions).

### ✅ 2. Non-POS sale discounts are not accepted by request validation — Fixed

`StoreSaleRequest` now preserves the optional per-unit discount through validation, requires it to be numeric and nonnegative, and caps it at the corresponding line's unit price. The HTTP path now calculates and persists the discounted subtotal, tax, sale total, line discount, and payment amount consistently.

Evidence: [api/app/Http/Requests/StoreSaleRequest.php](api/app/Http/Requests/StoreSaleRequest.php), [api/app/Services/SaleItemService.php](api/app/Services/SaleItemService.php), and [api/tests/Feature/TenantIsolationTest.php](api/tests/Feature/TenantIsolationTest.php).

**Verified:** Docker tests confirm a valid discount produces a $1,600 subtotal, $288 tax, and $1,888 sale/payment total, and reject negative and over-unit-price discounts. The focused non-POS sale suite passed (6 tests, 33 assertions).

## P1 — Resolve Before Release

### ✅ 3. `siteadmin` has no frontend dashboard route — Fixed

`AppRoutes` now mounts the existing Superadmin route tree for both seeded platform roles, `CoreSupport` and `siteadmin`, so neither role falls through to the not-found route after login.

Evidence: [api/database/seeders/RoleTableSeeder.php](api/database/seeders/RoleTableSeeder.php) and [ui/src/app/routes/AppRoutes.tsx](ui/src/app/routes/AppRoutes.tsx).

**Verified:** TypeScript and Vite production build pass after the route update. A route-specific UI test is not configured in this project.

### ✅ 4. Platform-role permissions and tenant model scoping were inconsistent — Fixed

The platform-operator allowlist is now the single explicit exception in both business and branch scopes and includes `CoreSupport` and `siteadmin`. Ordinary elevated tenant roles such as `Executive` no longer receive unrestricted branch-scoped access when their account has no business; missing tenant context fails closed.

Evidence: [api/app/Models/BaseModel.php](api/app/Models/BaseModel.php), [api/app/Support/Auth/RolePermissions.php](api/app/Support/Auth/RolePermissions.php), [api/app/Support/Tenant/EffectiveBranchScope.php](api/app/Support/Tenant/EffectiveBranchScope.php), and [api/tests/Feature/TenantIsolationTest.php](api/tests/Feature/TenantIsolationTest.php).

**Verified:** Docker tests confirm both platform roles can query across business-scoped Roles and branch-scoped Products, while a businessless Executive sees neither. Existing branch and cross-business isolation checks also pass (10 tests, 24 assertions).

### 5. Backups are manual; recovery is not demonstrated

The PostgreSQL backup command can create a custom-format dump and restore it with an explicit destructive `--force` flag. It defaults to a local `storage/app/backups` path. The scheduler currently has no backup entry, and the repository does not establish off-host storage, retention/rotation, backup monitoring, or a tested restore drill. A backup command by itself is not a production recovery plan.

Evidence: [api/app/Console/Commands/DatabaseBackup.php](api/app/Console/Commands/DatabaseBackup.php) and [api/routes/console.php](api/routes/console.php).

**Action:** Automate backups to storage independent of the application host, define encryption/retention and failure alerting, and rehearse a restore into an isolated database. Record measured recovery point and recovery time objectives before launch.

### 6. Frontend lint is currently failing CI

The CI workflow runs `npm run lint`, so lint errors fail the frontend job. I reran the command after dependencies were restored: it reports **1,080 errors and 18 warnings**. This confirms the backlog recorded in the earlier review remains and is still a release/CI blocker.

Evidence: [.github/workflows/ci.yml](.github/workflows/ci.yml), [ui/package.json](ui/package.json), and [review.md](review.md).

**Action:** Reduce or intentionally configure the lint backlog, and keep lint plus the production build as required CI checks. The production TypeScript/Vite build passes; the full backend suite was not run during this task.

## P2 — Improve Before Wider Rollout

### 7. User-facing failure states are inconsistent

Some detail pages render generic text for request failures rather than a reusable error state with a retry or return action. For example, the product detail page displays a plain “Error loading product” block. A broader pass should ensure list, detail, mutation, and report pages distinguish loading, empty, permission-denied, and server-failure states. Do not let backend failures look like legitimate empty business data.

Evidence: [ui/src/app/pages/dashboards/executive/components/products/Product.tsx](ui/src/app/pages/dashboards/executive/components/products/Product.tsx). The size of the broader gap was not re-counted in this review.

**Action:** Establish shared error/empty/loading patterns, provide actionable retry behavior where safe, and review high-frequency POS, sales, inventory, and finance flows first.

### 8. POS resilience and mobile workflow need a deliberate launch decision

The UI project backlog still lists mobile support and offline sync as future work. A network-dependent POS may be acceptable for an initial connected pilot, but outages can stop sales and make reconciliation difficult. This is a product/operational decision rather than a claim that offline mode is already promised.

Evidence: [ui/README.md](ui/README.md).

**Action:** Validate the POS on the actual target tablet/phone and printer setup. For the first release, document the connectivity assumption and recovery procedure; plan offline sale queuing and conflict-safe stock reconciliation before expanding to low-connectivity locations.

### 9. Auditability and recovery paths should be validated end to end

The application has stock movements, product-loss records, financial audits, and sale/purchase return flows. Before launch, verify that each correction flow reverses or compensates the original ledger effects without silently rewriting completed business history. Existing breadth is promising, but the review did not execute a full business UAT across these linked records.

**Action:** Run scripted end-to-end scenarios for sale, partial return, purchase receipt/return, stock write-off, cash adjustment, and void/refusal paths. Assert final stock, sale/payment totals, cash flow, audit log, and branch ownership after each scenario.

## Areas That Have Improved Since Older Reviews

Do not carry these older findings forward as current blockers without new evidence:

- `procurementApi` is now registered as both reducer and middleware in [ui/src/app/store/app/store.ts](ui/src/app/store/app/store.ts).
- Dashboard routes now mount under `/dashboard/*`, and the role route trees have not-found fallbacks; the earlier exact-path and broad dead-link claims are stale. See [ui/src/app/routes/AppRoutes.tsx](ui/src/app/routes/AppRoutes.tsx).
- `CoreSupport` and `siteadmin` now share explicit platform-operator scope semantics, and both map to the Superadmin route tree. Regression tests cover business- and branch-scoped models and fail-closed behavior for a businessless Executive.
- Non-POS sales now lock selected branch products and reject products from another branch in [api/app/Services/SaleItemService.php](api/app/Services/SaleItemService.php).
- POS receipt numbering now uses `ReceiptNumberGenerator`, rather than the earlier count-and-increment approach, in [api/app/Services/PosService.php](api/app/Services/PosService.php).
- PHPUnit configuration forces the `testing` environment and `inventory_test`, which addresses the prior development-database safety issue. See [api/phpunit.xml](api/phpunit.xml) and [api/.env.testing](api/.env.testing).
- Completed-sale immutability and the earlier sale relation/totals fixes are recorded as implemented in [review.md](review.md).

## Recommended Order

1. ✅ Fix aggregate stock validation and add the non-POS duplicate-line regression test.
2. ✅ Restore discount validation at the HTTP boundary and test final monetary values end to end.
3. ✅ Align `CoreSupport`/`siteadmin` backend scope semantics and frontend role routing.
4. Implement scheduled off-host backups and complete a documented restore drill.
5. Re-run the full backend suite, frontend lint, and production build in the same CI configuration used for release.
6. Complete role-based UAT on real devices, then decide the first-release connectivity guarantee for POS.

## Review Limitations

This was a source-based review, not a production penetration test or full manual UAT. Current validation for this task included 10 targeted backend tests (24 assertions), a successful TypeScript/Vite production build, and an ESLint run that remains failing at 1,080 errors and 18 warnings. No external integrations, messaging/notification delivery, or URA flows were assessed.
