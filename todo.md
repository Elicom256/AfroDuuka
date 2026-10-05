# Todo Module
## Problem
When the user tries to access the todo page (http://localhost/dashboard/todos), he lands on a 404 page.
## Task
Analyse and find out the root cause, then fix it permanently. Before doing anything, please plan for the task down in this file under **task plan**
##Constraints
Read rules.md

## Task Plan

### Root cause
`/dashboard/todos` 404s because the route is registered in **one role tree only**.

- `AppRoutes.tsx:164` mounts `dashboard/*` into a tree chosen by role
  (`ROLE_DASHBOARD_TREE` in `ui/src/lib/roles.ts`).
- `path='todos'` exists only in `ExecutiveRoutes.tsx:115`. It is absent from
  `BranchManagerRoutes`, `OperationsRoutes` and `ProcurementRoutes`.
- Every tree ends with its own `<Route path='*' element={<NotFound />} />`, so any
  non-Executive hitting `/dashboard/todos` lands on the 404 page.

The inconsistency is in the UI, not the API:

- `api/routes/users.php:39` exposes `Route::apiResource('todos', TodoController::class)`
  under `auth:sanctum` only, with no role middleware, so **every authenticated user**
  may use todos.
- `AuthorizationPolicyCoverageTest:121` asserts a BranchManager can update a todo,
  which encodes the intent that a BranchManager uses this feature.
- `BranchManagerSidebar.tsx:64` advertises a "Tasks → Todos" item pointing at
  `/dashboard/todos`, so the app actively sends BranchManagers into the 404.

Not the cause (ruled out by inspection): the component and query both exist and build
clean; `TodoController` and the `Todo` migration exist; `todoQuery` resolves to
`/api/users/todos`, which matches the backend route and the existing passing API test;
`ExecutiveSidebar`'s `/todos` is correct because it is prefixed with `/dashboard`.

### Status — done (Phase 1 and Phase 2 complete)

#### Phase 1 — unblock the BranchManager 404 (the reported bug) ✅
1. Declared `path='todos'` and `path='create-todo'` in `BranchManagerRoutes.tsx`,
   importing the existing `TodoList` / `TodoForm`.
2. `ExecutiveRoutes.tsx` left as it is; its routes already worked.

**Why the routes are declared per tree rather than extracted into a shared component:**
React Router's `<Routes>` accepts only `<Route>` elements and inline fragments as
children. A custom component that returns routes is silently ignored, so a
`TodosRoutes` component mounted as a child would have registered nothing and left the
404 in place while looking like a fix. Declaring the two routes in each tree is also
the pattern this codebase already uses — `BranchManagerRoutes.tsx` mounts
`ExecutiveFinanceTransactionsPage`, `ExecutiveFinanceReportsPage` and
`ExecutiveFinanceCashFlowPage` the same way, reusing components across trees without a
shared route abstraction (`rules.md`: preserve existing patterns, reuse components).

#### Phase 2 — todos on every remaining dashboard ✅
3. Declared the same two routes in `OperationsRoutes.tsx`, `ProcurementRoutes.tsx`,
   `Superadmin.tsx` (covers `siteadmin` and `coresupport`) and `StaffDashboard.tsx`.
4. Added a "Tasks → Todos" nav entry to the sidebars that lacked one: Operations,
   Staff (as a section) and Procurement, Superadmin (as flat items — Superadmin's list
   holds full `/dashboard/...` paths rather than using the `prefix` the others use).
   Executive and BranchManager already had one.
5. API untouched — it was already correct and already tested.

### Tests
`ui/src/app/routes/todosRoutes.test.tsx` — 15 tests, all passing, following the existing
`publicRoutes.test.tsx` pattern (vitest, testing-library, the **real** app store):

- `/dashboard/todos` renders the todos page rather than `NotFound` for Executive,
  BranchManager, Operations, Procurement, siteadmin and CoreSupport — asserted by role,
  so a test that only rendered ExecutiveRoutes could not pass while the bug was live.
- `/dashboard/create-todo` resolves for BranchManager.
- an unknown dashboard path still reaches `NotFound`, so the route did not replace the
  catch-all.
- a role outside `ROLE_DASHBOARD_TREE` is still refused.
- every sidebar's Todos link points at `/dashboard/todos`.

Two failure modes were hit and fixed while writing these, both worth recording:
- Asserting the sidebar through the whole `/dashboard` page made the result depend on
  every widget's data shape (`RecentSales` threw `sales is not iterable` against a
  generic stub). The sidebar components are now rendered directly.
- Every sidebar renders `to={!role ? '/login' : itemPath}`, so asserting before
  `/users/me` resolved read the pre-session render; ProcurementSidebar lost that race.
  The test now waits for the session query to settle.

### Verification
- `docker compose exec -T backend php artisan test` → **717 passed, 2097 assertions**
- `cd ui && npx tsc --noEmit` → clean
- `cd ui && npx vite build --mode development` → built
- `cd ui && npx vitest run` → **75 passed** across 5 files
- `docker compose exec -T backend ./vendor/bin/pint --test` → untouched by this task
- Mutation-checked: removing the BranchManager route, and pointing its nav link at a
  path with no route, each fail the suite.

### Out of scope (noted, not changed)
- Whether a *platform* operator (`siteadmin`/`coresupport`) should keep personal todos
  at all is a product question. The route is there because the brief asked for todos on
  every dashboard; the API treats them as ordinary per-user rows.

### Follow-up — mangled comment in `api/routes/users.php` ✅
The Todos banner comment had an orphaned `->only([...])` fragment left inside it, the
leftover of an edit that moved the call onto the wrong line. Corrected to a plain banner
with the intent restored on the call itself, matching the `->only([...])` style the
notifications resource in the same file already uses.

Restoring it changes nothing: `route:list --path=users/todos` reports the same five
actions before and after. Worth correcting the earlier assumption — `apiResource`
registers only index/store/show/update/destroy, unlike `resource` which also registers
`create` and `edit`, so the bare call was never exposing broken endpoints and
`TodoController` was never missing methods it was routed to. The fragment was redundant,
not load-bearing. A repo-wide grep for the same orphaned-fragment pattern found no
others.



