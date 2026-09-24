# Scope Branch — tenant isolation plan (Roadmap 3.1)

Status: **PROPOSAL — not yet implemented.** Review this before any code changes.
Owner-approved direction: *branch scoping for everyone, core-admin goes through business_id.*

---

## 1. Decision (from owner)

- Every row that carries tenant data is scoped by **business_branch_id**.
- Normal users (staff/editor/manager/worker with a branch set) see **only their branch**:
  `business_branch_id = Auth::user()->business_branch_id`.
- **Superadmin** (business-level admin, has `business_id`, no branch restriction) sees **all
  branches of their business**: resolved via `business_id -> all business_branch_id of that
  business`.
- **Systemadmin** (true system role, me — no `business_id` at all) is **unrestricted** — the only
  way an unscoped query is legitimate.
- Each branch also has its own **admin** role (`admin` at branch level) = every other branch user.
- Everything else is a bug.

This produces a strict rule:

> A user may touch a row **iff** that row's `business_branch_id` is inside the user's
> **effective branch set**.

---

## 2. Current state (verified against code + Postgres)

- `BaseModel` (app/Models/BaseModel.php) global scope only filters on `business_id` and only
  auto-fills `business_id`. The per-`business_branch_id` scope exists **commented out** at
  lines 19–23 (the roadmap quotes 19:23).
- `products` table (Postgres `\d products`) has `business_branch_id` and **no** `business_id`.
- `Product` model has no `business_id` field and its show is `findOrFail($product)` unscoped.
- `ProductController::show` at line 41 is unscoped; `update`/`destroy` also use route-model
  binding `Product $product` — which resolves by id without any branch check.
- `ProductController::index` scopes only by `business_branch_id` (correct for branch users,
  wrong for core admin, which sees nothing except its own branch).
- Controllers are inconsistent: some scope reminders by branch in index but not show, some never
  scope. Policies exist (ProductPolicy etc.) but **none are invoked** — the roadmap confirms
  `$this->authorize()` is never called.
- Roles are stored in a `roles` table keyed by `name` (admin = business-level, others = branch).

---

## 3. Design

### 3.1 Central decision point

Introduce ONE helper that answers "which branches may the current user see?" — everything else
calls it. No controller re-implements its own branch logic.

```
App\Support\Tenant\EffectiveBranchScope
    static branchesFor(?User $u): ?array   // [] => unrestricted (superadmin)
```

Resolution order for a user `u`:
1. `u` has no `business_id` → **unrestricted** (`null` / skip scope).
2. `u` has `business_id` but no `business_branch_id` (core admin) →
   `BusinessBranch::where('business_id', u.business_id)->pluck('id')` (all branches).
3. else → `[u.business_branch_id]`.

### 3.2 Enforcement layers (defense in depth, in order)

**L1 — Global scope on BaseModel (the floor).** All tenant models extend `BaseModel`/`BaseModel`.
Make the global scope branch-aware:

- branch user → `where business_branch_id = u.business_branch_id`
- core admin → `whereIn business_branch_id IN (all branches of u.business_id)`
- superadmin → no constraint

Keep auto-fill for `business_id` and also auto-fill `business_branch_id` on create (currently
commented out — uncomment, it's the same pattern as `business_id`).

Note: `products` has no `business_id` column, so L1 for Product uses `business_branch_id` only —
that's correct and sufficient once core-admin expands to all branches of the business.

**L2 — Browse/Router layering (the check on write).** Even with L1, `findOrFail($id)` /
route-model binding `Product $product` bypasses the global scope **if the scope is disabled or
if the row is fetched through a relation/query that doesn't use it**. So:

- Replace unscoped `findOrFail($id)` in `show/update/destroy` with a scoped resolver:
  `Product::findOrFail($id)` is fine **as long as L1 applies**; but make it explicit and test it.
  Use `Product::whereBusinessBranchIdInScope(...)->findOrFail($id)` style so it's auditable, or
  rely on L1 + a feature test proving cross-tenant access returns 404.

**L3 — Policies (the authorization contract, actually invoked).** Product hides are an explicit
roadmap mention:
- Invoke `$this->authorize(...)` at the start of `show/update/destroy` (and `store`).
- Route policies in `app/Policies` (ProductPolicy etc. currently return `false` stubs).
- In `ProductPolicy::view/update/delete`, check the row is inside the caller's effective branch
  set (same helper as L1) and that caller is a role that may act on products — not blanket `false`.

### 3.3 Scoping rule must hold on WRITE endpoints too

- `store` must auto-fill `business_branch_id` from the authenticated user (or reject if the
  request tries to set another branch). No one may create a row in a branch they don't belong to.
- `update/destroy` must resolve the model through L1 **then** confirm the branch hasn't been
  changed to one outside the user's set (e.g. a manager moving a product into an unrelated branch).

---

## 4. Concrete changes in this pass

Scope: **roadmap item 3.1 only.** (Not 3.2 roles, not 3.4 jobs, not seeders.)

1. Add `app/Support/Tenant/EffectiveBranchScope.php` helper.
2. Rework `BaseModel` global scope to branch-aware (L1) + uncomment `business_branch_id` auto-fill.
3. Apply the same global-scope/shaping story to `Product` (the roadmap's headline model) —
   verify `products` participates via `BaseModel`.
4. Make `ProductController::show/update/destroy` resolve through the scoped path and add
   `$this->authorize(...)` calls (L2 + L3). Fix `index` to respect core-admin (all branches).
5. Fix `ProductPolicy` (and the subset of sibling policies the roadmap explicitly names) to use
   the branch-set check instead of blanket `false`, and ensure `app/Providers/AuthServiceProvider`
   (or auto-discovery) registers them.
6. Add feature tests proving cross-tenant access is denied (404/403) and core-admin can see all
   branches.

**Explicitly NOT in this pass:** branch-scope every one of the 60 controllers, 3.2 role logic,
dangling-policy sweep for all models, UI changes. Those are follow-ups.

---

## 5. Ambiguities to confirm with you

- **Branch-scope semantics for employees of branches:** OK that a manager/editor on branch A
  cannot see branch B rows even within the same business? (I assume YES = that's the whole point.)
- **Core admin defined by `roles.name = 'admin'` and no `business_branch_id`** — confirm the
  seed/role data matches (admin role must NOT carry a branch).
- **Audit/activity logs:** should the `business_id` auto-fill on BaseModel stay as-is (business
  level) while row scoping becomes branch-level? (Likely yes — logs remain business-level.)
- **404 vs 403** for cross-tenant reads: I plan **404** (don't reveal row existence) for read
  endpoints, 403 for write attempts. Confirm preference.

---

## 6. Risks / notes

- A global scope on `BaseModel` touches **every** tenant model at once — a subtle bug becomes a
  site-wide leak. Mitigate by making L1 additive (never drops the `business_branch_id` of rows a
  superadmin or core admin should see) and by landing it with the feature tests.
- `products` lacking `business_id` is by-design per the schema; don't add the column unless we
  later need cross-branch business-level queries.
- Verify tests run: the app's tests use SQLite `:memory:`; this machine has no `pdo_sqlite` and
  no reachable Postgres from PHP — so we'll run migrations/tests inside Docker
  (`duukaflow-pgsql-1` is where Postgres lives) and flag anything not runnable locally.
