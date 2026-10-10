# Fix: the items in `note.md`, easiest first

The `bugs.md` ranking (items 1–20) is complete; `note.md` records things the chunks
turned up that were deliberately **not** fixed, each with the reason it was left
alone. This plan picks those back up. One chunk at a time, pushed after each; mark
the row `**done**` and rewrite the plan for the next one.

## Ranking (easiest -> hardest)

Ties broken by blast radius: cheap-and-unblocks-others ranks above
cheap-and-isolated. Items that needed a product/schema decision before being
touchable are ranked where their dependency clears.

| # | Item | Root cause / why it was left | Complexity | |
|---|------|------------------------------|-----------|-|
| 1 | Dead file: `ExecutiveFinancesPage.tsx` | Imported nowhere and not routed; the "View All Transactions" button on it is unreachable | LOW | **done** |
| 2 | Person-naming sweep (`?.name`) | `Customer`/`Supplier`/`User` have no `name` column; the item-5 fault "is probably not the last place it happens" | LOW-MED | |
| 3 | Analytics: `total_products` never returned | Both analytics pages read `analytics.data.total_products`; `ProductService::analytics()` returns `lowStock`/`outOfStock`/totals, not that key — pages show zeros | LOW-MED | |
| 4 | `todosRoutes.test.tsx` flake | Failed once on a clean checkout in chunk 1, never diagnosed; green in chunks 2+ but treat as flaky | MED | |
| 5 | Audit dialogs branch mismatch | `GET /api/products` takes no branch param but the dialogs' dropdown does; `StoreProductAuditRequest` validates with bare `exists:products,id` then `createAudit()` branch-scoped `findOrFail`. Deferred pending item 13, which is now fixed | MED | |
| 6 | xlsx export conversion | No `maatwebsite/excel`/`phpspreadsheet`; needs new dependency + `zip`/`xml` PHP extensions in Docker; `ExportButton.tsx` hardcodes a `.csv` download name | MED-HIGH | |
| 7 | cost-at-sale column | Per-product profit needs the cost the sale was made at; `sale_items` stores no cost. Schema decision, not a bug fix | HIGH | |

---

## Chunk 1 plan: item 1 — delete dead `ExecutiveFinancesPage.tsx`

`ui/src/app/pages/dashboards/executive/pages/ExecutiveFinancesPage.tsx` exports
`ExecutiveFinancesPage`, which is imported nowhere and not routed. The sidebar's
Financials section links Cash Flow (`/cashflow`), Transactions
(`/finance/transactions`) and Reports (`/finance/reports`) — all to real pages, none
to a `/finances` or `/financials` route. The file's only unique feature, the "View
All Transactions" button, is unreachable.

Grep confirms: no import references, no tests, no dynamic-import string.

### Files to modify

- Deleted `ui/src/app/pages/dashboards/executive/pages/ExecutiveFinancesPage.tsx`.
- Also deleted `ui/src/app/pages/dashboards/executive/pages/executive-placeholder-pages.tsx` — the whole file was dead: it re-exported a *second* `ExecutiveFinancesPage` (line 120) plus placeholder versions of pages that all live in their own routed files (`ExecutiveCustomersPage`, `ExecutiveAnalyticsPage`, `ExecutiveReportsPage`, `ExecutiveSuppliersPage`, `ExecutivePromotionsPage`, `ExecutiveCouponsPage`, `ExecutiveSettingsPage`). Nothing imports or routes any of them.

### Verification

- `npx tsc -b` clean.
- `npx vitest run` — 801 passed (18 files). Test count dropped 805→801 because `themeContrast.test.ts` walks the file tree and fits over every `.tsx`; the 2 removed files were compliant (used `bg-muted`/`text-muted-foreground`), so only their parameterised entries disappeared.
- Grep confirms zero remaining references to either export anywhere in `ui/src`.