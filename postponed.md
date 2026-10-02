# Postponed

## FrontendApiPathsTest — 20 mismatches (2026-10-02)

`api/tests/Feature/FrontendApiPathsTest.php` was added to guard against frontend/backend
API drift (wrong paths, wrong HTTP verbs). It currently reports 20 mismatches.

### Parsing artifacts (test bugs, not app bugs)

The test's regex captures query strings as part of the URL path. These need to be
stripped before comparing against backend routes:

- `cashFlowQuery.ts` — `?page=1`
- `expenseQuery.ts` — `?${qs}`, `?${searchParams.toString()}`
- `productAuditQuery.ts` — `?${qs}`
- `financialAuditQuery.ts` — `?${qs}`
- `purchasesQuery.ts` — `?period=last_7_days`

Fix: strip everything after `?` from the captured URL before path comparison.

### Possible real mismatches (need backend route verification)

These may be genuine missing routes or wrong paths in the frontend:

| File | Method | Path |
|---|---|---|
| `inventoryQuery.ts` | GET | `api/inventory` |
| `branchesQuery.ts` | GET | `api/dashboard/branches/branch/dynamics` |
| `expenseQuery.ts` | POST | `api/expenses/branch-expenses/1/approve` |
| `purchasesQuery.ts` | DELETE | `api/purchases/branch-purchases/1` |
| `branchSuppliersQuery.ts` | GET | `api/suppliers/1` |
| `branchCustomersQuery.ts` | GET | `api/customers/1` |
| `salesQuery.ts` | DELETE | `api/sales/branch-sales/1` |
| `authQuery.ts` | PATCH | `api/users/update` |

### Also noted

- `branchSuppliersQuery.ts` and `branchCustomersQuery.ts` use `/suppliers` and `/customers`
  as baseUrls, but the canonical routes are `/dashboard/suppliers` and `/dashboard/customers`.
  These may be intentional (different route group) or may be bugs — needs verification.
- `messagesQuery.ts` uses `/messages` — verify this route exists.
- `branchWorkersQuery.ts` POSTs to `/users/workers` — verify this matches the backend.

### Status

Test is written and running but failing. Needs the query-string fix first, then
investigation of the remaining mismatches to determine which are real.
