# DuukaFlow — Local Readiness Review

**Date:** 2026-10-03
**Scope:** local implementation and product stability only.
**Status:** third-party integrations are intentionally deferred for now.

This review focuses on the issues that affect the product before any external integration work begins.

---

## Verified baseline

| Metric               | Result                   | Note                                                                  |
| -------------------- | ------------------------ | --------------------------------------------------------------------- |
| Backend tests        | **593 passed**           | 1764 assertions, full suite green                                    |
| TypeScript           | **Passes**               | `tsc -b --force` completes successfully                               |
| ESLint               | **1,098 findings**       | 1,080 errors, 18 warnings across 451 files; lint remains red          |
| CI backend           | **Not reliably green**   | Configuration is inconsistent for database-backed tests               |
| Local data integrity | **Needs hardening**      | Sale and stock paths still drift                                      |
| Route drift          | **Confirmed**            | Frontend and backend contracts are not aligned                        |

---

## Local issues to fix first

### ✅ 1. Non-POS sales can still fail and roll back the transaction

This issue is fixed: the stale `salePayment` relation load has been replaced with the valid `salePayments` relation, so a successful non-POS sale can complete without throwing inside the transaction.

Action completed:

- removed the stale relationship from the sale load path
- added a regression test for a successful non-POS sale

### ✅ 2. POS and non-POS checkout paths calculate totals differently

The two sale paths do not agree on discount, subtotal, and tax treatment. The non-POS flow can drop discount data and under-record stock movement, which leads to inconsistent ledger values and tax mismatch.

Action:

- align both sale paths to the same finalised-sale logic
- pass discounts through consistently
- ensure stock movement rows are created for both paths

Implementation is complete: the non-POS flow now applies per-unit discounts to line tax and subtotal, validates discount input, and records an idempotent outbound stock movement. The regression fixture explicitly selects tax-exclusive pricing. All tests in `TenantIsolationTest` pass, including `test_sale_item_service_applies_discount_to_tax_and_subtotal`.

### ✅ 3. Completed sales remain mutable

A completed sale can still be updated and re-pointed without a guard on status. This leaves the system exposed to post-completion mutation.

Action:

- prevent updates to completed sales
- enforce a return/edit flow instead of direct mutation
- add a test for status-based immutability

Implementation is complete: direct updates to completed sales now return `409 Conflict`. The immutability check is enforced in `SaleController@update` and verified by `test_completed_sale_cannot_be_updated` in `TenantIsolationTest`. We also fixed the underlying route-model binding mismatch so the guard hits the persisted sale, not an empty instance.

### ✅ 4. Frontend/backend route drift

The frontend and backend contracts are not aligned. The API drift test is already catching real mismatches, including missing endpoints and invalid route names.

Examples:

- `GET /inventory` has no backend route
- `DELETE /sales/branch-sales/{id}` is not actually exposed
- `GET /suppliers` and `GET /customers` map to different backend endpoints
- worker/user creation/update routes are inconsistent

Action:

- fix the route mismatches at the source
- keep the API drift check running in CI
- normalize query-string handling before comparing routes

All route mismatches identified have been corrected. `Tests\Feature\FrontendApiPathsTest` now passes with no reported drift; expense approval, monthly summary and totals endpoints expose the expected branch-expenses-scoped paths, and dead message slice references removed from the frontend. The drift checker strips query strings to avoid parsing artifacts.

### ✅ 5. auth and policy coverage is incomplete

Several controllers lack effective authorization checks, and policies are not consistently enforced across the app.

Action:

- audit controllers for missing `authorize()` usage
- ensure tenant and role checks are enforced centrally
- add policy coverage for modules currently bypassing checks

Progress: role management now requires an elevated role because role definitions are business-wide, and create/update requests are authorized and validated. Product catalogue updates now require catalogue permission, with the Operations stock-count path preserved. Purchase-order create, update, approve, order, receive, cancel, and delete actions now enforce the existing role capabilities across both API paths. Additional regression tests added for these areas (Procurement permissions in `OrderTest`, catalogue permission in `OperationsRolePermissionsTest`). A route-model binding guard (`RouteModelBindingTest`) was added to prevent silent unbound injections that were masking the completed-sale guard before.

The audit was then re-run mechanically over every mutating route rather than by reading controllers, which corrected two earlier mistakes. The first pass counted `auth:sanctum` as a gate, but authentication is not authorization; the second missed that the `role` middleware alias takes no parameter, so whole route groups looked unguarded. With both fixed, 19 mutating endpoints remain without a role or policy gate, and each was reviewed and confirmed intentional: `login`/`signup` are public by design, `logout` only revokes the caller's own tokens, the four notification endpoints are scoped to `user_id = Auth::id()`, `POS` cart/held-sale routes are the seller's own floor actions (`deleteHeldSale` filters on `user_id` and `status = 'held'`), and `BusinessDebitController::pay` records money already committed. Every `DELETE` is covered centrally.

That central coverage was itself the substantive fix. `BlockRestrictedRoleActions` denied deletes by asking `RolePermissions::isRestricted()`, which is "deny Operations" — correct only while Operations is the sole role without delete rights. It is not: Procurement has none either, but being absent from `RESTRICTED_ROLES` it passed through every delete in the app, tenant-wide finance rows included. It now asks `canDelete()`, an allowlist that fails closed for any role added later. A missing user still passes through, so an expired token yields the 401 the SPA's `authListener` depends on rather than a 403.

Also closed: `routes/stock-transfers.php` published `PUT`/`PATCH` to a `StockTransferController::update` that did not exist, so the route was a guaranteed 500 — it now edits draft transfers only, and refuses once stock has moved. `PurchaseController::update`/`destroy` were empty bodies returning `null`; they now refuse explicitly with a reason rather than silently doing nothing, since a received purchase is referenced by `stock_movements` and by product cost basis. `AiController::chat` is held to elevated roles, because each call is an unmetered billable round trip to a model provider.

One candidate was reverted rather than shipped. Gating `BusinessDebitController::pay` to `canManageBranch()` broke four tests in `BusinessDebitTest` whose Operations user settles debts deliberately. That test is the better statement of intent: settling a debt records a decision already made, whereas approving an expense decides whether spend is allowed, and this app has no separate accountant role.

Coverage added in `AuthorizationPolicyCoverageTest` and `MutatingEndpointAuthorizationTest`. **593 tests pass (1764 assertions)**, including a regression that pins Procurement out of deletes and a branch manager still able to delete within their branch.

### 6. CI and environment sanity still need attention (But should keep untracked for now)

- backend configuration expects PostgreSQL, but CI is not consistently wired for it
- frontend lint remains red
- the repo still has environment and secret hygiene issues that should be cleaned before adding any new integration layer

Progress: the local CI workflow now provisions PostgreSQL and the frontend source path required by the API drift check; Compose development credentials are aligned with `api/.env.example`. The `.env.prod` file has been removed from Git and ignored for future copies. Credential rotation/history cleanup and the existing frontend lint backlog remain open. The Docker lint run found 1,080 errors and 18 warnings across 451 files, mostly `no-explicit-any`, unused variables, and React hook rules; only one finding is auto-fixable. Keep `.github/workflows/ci.yml` untracked for now. Final Docker test verification is deferred until the local issue list is complete.

---

## Recommended next order of work

1. fix the non-POS sale rollback
2. align POS and non-POS sale calculations
3. lock completed sales against mutation
4. close the frontend/backend route drift
5. tighten local authorization and policy enforcement
6. only then revisit external integrations

---

## Scope note

For now, the review intentionally excludes third-party integrations. Once the local sale, stock, auth, and route integrity issues are resolved, the integration work can be reviewed separately and scoped cleanly.
