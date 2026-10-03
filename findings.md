# DuukaFlow Review Findings

## Scope

This review covers the core operational product: inventory, POS, sales, procurement, stock movement, finance, workers, suppliers/customers, RBAC, reporting, and UI flow.

Excluded from scope for this review:

- WhatsApp messaging and notification infrastructure
- Email delivery and notification channels
- URA fiscalisation / e-invoicing
- external API integrations and paid third-party services

The project is materially larger than a simple inventory app, and there are strong signs of real product work. The main issue is not lack of feature breadth; it is a combination of launch blockers, route integrity problems, and authorization gaps that are serious enough to merit a disciplined cleanup before production rollout.

---

## Executive summary

DuukaFlow appears to have a substantial amount of business-domain work already implemented. The backend includes multi-branch inventory logic, purchase and sale flows, stock movement tracking, employee and branch structures, role-based access concepts, and reporting/Audit surfaces. The UI also includes a broad set of dashboards and screens for operational work.

That said, the codebase is not launch-ready. The biggest risks are not in the excluded notification/API areas; they are in:

- tenant isolation and authorization
- route coverage and navigation correctness
- procurement module integration
- data consistency in sale/stock paths
- test environment correctness
- general UI reliability and stale state

Several of the most serious findings are active correctness or security concerns rather than cosmetic polish. The application needs a short, disciplined hardening pass before going live.

---

## What is already in place

The repo shows real product maturity across a number of core operational domains:

- Multi-branch inventory and stock management
- Core POS and checkout flows
- Sales and purchase records
- Customer/supplier/worker management
- Expense and finance-related modules
- Reporting and audit-style surfaces
- RBAC concepts and policy scaffolding
- Database migrations covering a broad business domain
- A React + Vite frontend with many modules and route trees

The strongest area is the core business layer: the app is not a stub or a demo. It is a real operational system with a wide footprint.

---

## Main findings

### 1. Authorization is not consistently enforced

This is the most important backend issue.

The review evidence shows that some routes are exposed without the correct role guards, enabling cross-tenant or over-privileged actions. Notable examples include:

- super-admin routes without role enforcement in [api/routes/super-admin.php](api/routes/super-admin.php)
- user deletion routes outside the guarded role group in [api/routes/users.php](api/routes/users.php)
- tenant-sensitive actions being reachable from non-siteadmin flows due to weak scope behavior in the tenant context code

This is a launch blocker because it affects business isolation and administrative safety.

### 2. Route and navigation integrity is brittle

The frontend review shows that many links and route patterns do not behave correctly. A broad set of row-click handlers and page transitions point to paths that resolve to blank screens or 404s.

Examples include:

- [ui/src/app/routes/AppRoutes.tsx](ui/src/app/routes/AppRoutes.tsx)
- [ui/src/app/routes/ExecutiveRoutes.tsx](ui/src/app/routes/ExecutiveRoutes.tsx)
- [ui/src/app/routes/OperationsRoutes.tsx](ui/src/app/routes/OperationsRoutes.tsx)
- [ui/src/app/routes/BranchManagerRoutes.tsx](ui/src/app/routes/BranchManagerRoutes.tsx)

The issue is not just cosmetic; users may be unable to reach the intended pages and lose confidence in the product almost immediately.

### 3. Procurement is structurally disconnected from the app state

The procurement API slice appears to exist but is not registered in the Redux store. That prevents the module from rendering or functioning properly.

This is visible in the frontend store setup and the procurement feature files, especially:

- [ui/src/app/store/app/store.ts](ui/src/app/store/app/store.ts)
- [ui/src/app/store/features/procurement/procurementQuery.ts](ui/src/app/store/features/procurement/procurementQuery.ts)

The result is a real module that appears to exist in code but does not participate in runtime state, which is a serious integration defect.

### 4. Sale flow correctness matters more than feature count

The critical local business problem is not a missing UI button; it is whether the legal flow of a sale is stable and consistent.

The review evidence points to:

- inconsistent discount/tax handling between sale paths
- missing or broken stock movement recording in some paths
- incorrect stock decrement timing in some low-stock checks
- completed sales still being mutable in some update paths
- non-POS sale failures due to relation mismatch issues

The financial and stock integrity of the application should be treated as a primary concern before production readiness.

Observed areas include:

- [api/app/Services/SaleItemService.php](api/app/Services/SaleItemService.php)
- [api/app/Services/PosService.php](api/app/Services/PosService.php)
- [api/app/Models/Sale.php](api/app/Models/Sale.php)
- [api/routes/sales.php](api/routes/sales.php)

### 5. The test environment and DB setup need to be disciplined

The review highlights an important issue: tests should not be run against the live dev database. A proper test database is required, and the setup should be hardened so the suite validates changes without risking local data.

This is a major operational issue because test reliability drives release confidence.

### 6. Data and state freshness is weak in several UX flows

The frontend review notes stale cache behavior and insufficient cache invalidation after actions like sales, inventory updates, and finance changes. In practical product terms, users may keep seeing old stock or old record counts even after updates succeed.

This is a real user experience and operational trust issue.

### 7. Launch blockers are more about correctness than missing features

The project is not failing because it lacks feature scope. It fails because several basic guarantees are not yet reliable enough for production:

- permissions are not enforced consistently
- role-based access is not fully aligned with route exposure
- navigation is broken in multiple key modules
- stock and sale flows are not uniformly correct
- the app does not fail closed on auth errors or broken state
- the app is not fully guarded against stale or inconsistent client-state flows

This is more damaging than optional roadmap gaps.

---

## UI/UX observations

The UI has a lot of breadth and seems designed as a serious operations product, but there are clear quality issues:

- some key pages and links are dead or blank
- role-based navigation is incomplete for several seeded roles
- dark-theme contrast and accessible color usage are not consistently sufficient
- no strong global error boundaries or graceful state handling in major flows
- many row actions do not provide clear confirmation or safe feedback
- a large number of forms and tables are not robust against error states or invalid input

These are not launch blockers by themselves, but they do affect trust and adoption.

---

## Recommended priority order

### P0 — fix before launch

1. Lock down cross-tenant authorization and role-bound routes
2. Repair route trees and navigation targets
3. Fix the procurement state registration issue
4. Harden sale and stock consistency in the sale flow
5. Correct the test DB setup and make tests reliable
6. Remove empty or broken destructive actions and verify route safety

### P1 — before release candidate

1. Improve cache invalidation and data refresh behavior
2. Tighten auth failure handling and redirect logic
3. Add consistent validation and confirmation flows in high-risk actions
4. Repair remaining dead or blank screens across dashboards and tables

### P2 — before broader expansion

1. Improve accessibility and contrast consistency
2. Reduce duplicated UI code and tighten reusable patterns
3. Align the remaining role trees and page availability with seeded roles
4. Continue hardening reporting and audit flows

---

## Bottom line

DuukaFlow is not a toy app and it is not in a “just needs a few features” state. It has substantial product structure and operational domain coverage, but it still needs a focused launch-hardening pass on invariants, access control, route integrity, and sale/stock correctness.

The excluded messaging and API work is not the main constraint here. The real blocker is that the core product still has too many safety and correctness issues for a trustworthy production release.

If the goal is launch readiness, the most valuable work is not more feature expansion. It is closing the security and business-integrity gaps in the existing operational flows.
