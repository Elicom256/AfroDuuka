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

### Fix
Sequenced in two phases, as required.

#### Phase 1 — unblock the BranchManager 404 (the reported bug)
1. Declare `path='todos'` and `path='create-todo'` in `BranchManagerRoutes.tsx`,
   importing the existing `TodoList` / `TodoForm`.
2. Leave `ExecutiveRoutes.tsx` as it is; its routes already work.

**Why the routes are declared per tree rather than extracted into a shared component:**
React Router's `<Routes>` accepts only `<Route>` elements and inline fragments as
children. A custom component that returns routes is silently ignored, so a
`TodosRoutes` component mounted as a child would register nothing and leave the 404 in
place while looking like a fix. Declaring the two routes in each tree is also the
pattern this codebase already uses — `BranchManagerRoutes.tsx:90-92` mounts
`ExecutiveFinanceTransactionsPage`, `ExecutiveFinanceReportsPage` and
`ExecutiveFinanceCashFlowPage` the same way, reusing components across trees without a
shared route abstraction (`rules.md`: preserve existing patterns, reuse components).

#### Phase 2 — todos on every remaining dashboard
3. Declare the same two routes in `OperationsRoutes.tsx`, `ProcurementRoutes.tsx`,
   `Superadmin.tsx` (covers `siteadmin` and `coresupport`) and `StaffDashboard.tsx`.
4. Add a "Tasks → Todos" nav entry to the sidebars that lack one: Operations,
   Procurement, Superadmin and Staff. Executive and BranchManager already have it.
5. Leave the API untouched — it is already correct and already tested.

### Tests
- `ui/src/app/routes/todosRoutes.test.tsx`, following the existing
  `publicRoutes.test.tsx` pattern (vitest, testing-library, the **real** app store):
  - `/dashboard/todos` renders the todos page rather than `NotFound` for each role
    tree, including `BranchManager` — the regression this task is about.
  - an unrelated path under the same tree still hits `NotFound`, so adding the route
    did not swallow the catch-all.


### Verification
- `docker compose exec -T backend php artisan test` (api unaffected, must stay green)
- `cd ui && npx tsc --noEmit` and `npx vite build --mode development`
- `docker compose exec -T backend ./vendor/bin/pint --test` if api files change
- Manual: load `/dashboard/todos` as Executive, BranchManager and Operations

### Out of scope (noted, not changed)
- `rules.md` says not to introduce new business rules. Deciding *which* roles get a
  task list is a product decision, so step 2 is limited to roles that already have an
  authenticated dashboard tree and are already permitted by the API. Adding it to
  `Superadmin`/platform trees is deliberately left out.
- `api/routes/users.php:38` has a mangled comment (`->only([...])` orphaned into the
  comment line). Cosmetic; not part of this 404.

