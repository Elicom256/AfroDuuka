# DuukaFlow — Local Readiness Review

**Date:** 2026-10-03
**Scope:** local implementation and product stability only.
**Status:** third-party integrations are intentionally deferred for now.

This review focuses on the issues that affect the product before any external integration work begins.

---

## Verified baseline

| Metric               | Result                   | Note                                                                  |
| -------------------- | ------------------------ | --------------------------------------------------------------------- |
| Backend tests        | **534 passed, 1 failed** | The single failure is the API drift check, which is useful and active |
| TypeScript           | **Passes**               | `tsc -b --force` completes successfully                               |
| ESLint               | **1,099 problems**       | Frontend lint remains red                                             |
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

### 2. POS and non-POS checkout paths calculate totals differently

The two sale paths do not agree on discount, subtotal, and tax treatment. The non-POS flow can drop discount data and under-record stock movement, which leads to inconsistent ledger values and tax mismatch.

Action:

- align both sale paths to the same finalised-sale logic
- pass discounts through consistently
- ensure stock movement rows are created for both paths

### 3. Completed sales remain mutable

A completed sale can still be updated and re-pointed without a guard on status. This leaves the system exposed to post-completion mutation.

Action:

- prevent updates to completed sales
- enforce a return/edit flow instead of direct mutation
- add a test for status-based immutability

### 4. Frontend/backend route drift is still active

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

### 5. auth and policy coverage is incomplete

Several controllers lack effective authorization checks, and policies are not consistently enforced across the app.

Action:

- audit controllers for missing `authorize()` usage
- ensure tenant and role checks are enforced centrally
- add policy coverage for modules currently bypassing checks

### 6. CI and environment sanity still need attention

- backend configuration expects PostgreSQL, but CI is not consistently wired for it
- frontend lint remains red
- the repo still has environment and secret hygiene issues that should be cleaned before adding any new integration layer

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
