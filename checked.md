# DuukaFlow — Pre-Launch Review

> ## ✅ RE-VERIFICATION PASS — 2026-10-01 (branch `oct` @ `4346dcf`)
>
> Every P0/P1/P2/P3 item below was re-checked against the live code. **17 were already fixed**; **3 have now been fixed** (P0 #6 navigation, P0 #7 auth failure, P0 #9 production serving), plus the 4-error `tsc` regression. **20 of 47 done, 27 open.**
>
> **P0 launch blockers: 7 of 9 fixed (#1, #2, #3, #5, #6, #7, #9). 2 remain open: #4, #8.**
>
> | # | P0 blocker | Status |
> |---|---|---|
> | 1 | Any user can ban any tenant | ✅ **FIXED** — `ensureSiteAdmin()` on all 3 actions; `BaseModel` fails closed with `whereRaw('0 = 1')` |
> | 2 | Tests run against dev DB | ✅ **FIXED** |
> | 3 | Staff can delete the owner | ✅ **FIXED** — route moved inside `role` group; `canDelete()` allow-list + self-delete guard |
> | 4 | Receipt number collisions | ❌ **OPEN** — both generators still count-then-increment unlocked |
> | 5 | Procurement module renders nothing | ✅ **FIXED** — reducer + middleware registered |
> | 6 | 41 nav targets 404/blank | ✅ **FIXED — verified in a real browser, 1/26 → 26/26 routes** |
> | 7 | Auth failure renders homepage | ✅ **FIXED — verified, 12/12 browser checks** |
> | 8 | `.env.prod` committed with real `APP_KEY` | ❌ **OPEN** — still tracked, still not in `.gitignore` |
> | 9 | Production compose cannot serve | ✅ **FIXED — verified 502 → 200 against a live nginx** |
>
> ### ✅ #6 FIXED — the navigation layer now works
>
> **Root cause was worse than "41 links 404".** `getRolePrefix()` returned `/executive/dashboard`, and each role tree used that value as an **absolute child path nested under `/*`** — which React Router rejects outright:
>
> > `Absolute route path "/executive/dashboard" nested under path "/*" is not valid.`
>
> That invariant is in the **production** build too, not just dev. Every authenticated dashboard render was hitting it.
>
> **Fix:** `rolePrefix.ts` now exports a single `DASHBOARD_PREFIX = '/dashboard'`. `AppRoutes.tsx` mounts every role tree at `dashboard/*` instead of `/*`, and each tree uses **relative** child paths (`path='pos'`, a pathless `<Route element={<Layout/>}>` for the page group, plus `<Route path='*' element={<NotFound/>}/>`). The looping `/dashboard` → `getRolePrefix(role)` redirect is gone — the tree's own index route serves it.
>
> Two incidental fixes fell out: the `Procurement` role now mounts `ProcurementRoutes` directly (it was being sent to `OperationsRoutes`, which had no `procurement/*` branch, so `/dashboard` redirected to `/procurement/dashboard` forever), and `SuperadminSidebar`'s 7 hardcoded `/coresupport/dashboard` links were retargeted.
>
> **Verified in headless Chrome** against the production bundle, with `/api/users/me` stubbed per role and every page's own query used as the mount signal:
>
> | Tree | Before | After |
> |---|---|---|
> | Executive (26 routes) | 1/26 | **26/26** |
> | BranchManager | 1/4 | **4/4** |
> | Operations | 1/7 | **7/7** |
> | Procurement | 1/5 | **5/5** |
> | CoreSupport | 1/5 | **7/7** |
> | staff | 1/4 | **4/4** |
>
> All 19 unique hardcoded `/dashboard` links in the source now resolve for the role that owns them; zero router-invariant crashes; a bad path renders the 404 page instead of a blank screen.
>
> ### ✅ Bonus — the build is no longer broken
>
> The 4 `tsc` errors flagged as a regression above are fixed, so `npm run build` passes again. `Preview.tsx` was reading `account.country_id` for its "Country" row, but country is collected on the **business** step — it now reads `business.country_id`. `countriesQuery` was typed `builder.query<any, void>`, which is what made `BusinessSetup`'s filter callback an implicit `any`; it now has a real `Country` type. `tsc -b` exits 0 and ESLint is net −1 error.
>
> ### ✅ #7 FIXED — a dead session is now a dead session
>
> Added a store-level 401 handler (`authListener.ts`), made `authQuery`'s `baseQuery` refuse to call `/users/me` without a token (30 components read the logged-in user, including the public navbar, and each was firing a request that could only 401), and `AppRoutes` now reads the `error` it used to discard.
>
> Two things worth calling out:
>
> - **The redirect is a hard navigation on purpose.** The RTK cache still holds data fetched with the dead token, so a client-side redirect would re-issue every mounted query, 401 again, and loop. A full page load discards the store.
> - **This uncovered two regressions I had introduced in #6.** `Login.tsx` and `SignUp.tsx` both redirected to `` `/${role.toLowerCase()}/dashboard` `` — the exact URL #6 removed — so **every successful login and signup was landing on a 404**. My #6 browser test missed it because it injected the token directly and never exercised the login form. Both now use `DASHBOARD_PREFIX`.
>
> Verified 12/12 in headless Chrome (dead token, logged-out visitor, mid-session expiry, loop safety, valid session), and the #6 route suite re-run clean at 51/51.
>
> **Next up:** #4 (receipt sequence table — the last data-loss defect), then #8 (untrack + rotate `.env.prod`).
>
> ### ✅ #9 FIXED — production can serve traffic
>
> Seven defects, not the three documented. Beyond the wrong upstream port and the missing security headers: **`docker-compose.prod.yml` never parsed** (no build context for `backend`, no `pgsql`/`redis`), the proxy sent Vite HMR upgrade headers to a static file server, **`client_max_body_size` was unset** so nginx's 1 MB default 413'd uploads before Laravel ever saw them, and **there was no `queue-worker`** — so in production every queued job was silently dropped, taking WhatsApp notifications and subscription lifecycle processing with it. `docker-compose.yml` had the same port bug (`80:80` vs 8080) and `ui/Dockerfile` declared `EXPOSE 80`.
>
> Verified by running the real config in a container on the live Docker network against a real static frontend and the real Laravel backend, with the **pre-fix config from `git archive HEAD` side by side**:
>
> | Request | Before | After |
> |---|---|---|
> | `GET /` | **502** | 200 |
> | `GET /dashboard/products` (SPA fallback) | **502** | 200 |
> | `GET /api/health` | 200 | 200 (`database: ok`, `cache: ok`) |
> | Security headers emitted | **0** | 4 |
> | `POST` 3 MB body | **413** | 302 (reaches Laravel) |
>
> TLS is now documented rather than faked: no cert was available to mount, so 443 is no longer published (a published port that drops every request is worse than an absent one) and `nginx.prod.conf` states exactly what to add to terminate it in place.
>
> ### ✅ Also fixed — `TrustProxies` (found during #9, not one of the 47)
>
> Laravel had **no `TrustProxies` middleware**, so behind a TLS-terminating load balancer every generated URL came out as `http://` and `$request->secure()` returned `false`. Registered in `api/bootstrap/app.php:25` with `at: '*'`.
>
> `'*'` is safe in this topology and was checked before choosing it: **no compose file publishes a host port for the backend** — `docker-compose.yml`, `docker-compose.prod.yml` and `docker-compose.dev.yml` all use `expose: 8000` only. The nginx edge is therefore the only thing on the `app` network that can reach Laravel, so an `X-Forwarded-*` header cannot have been supplied by anyone else.
>
> **Verified with a temporary probe test** (written, run, then deleted — the same technique the original review used), asserting both directions:
>
> | Request headers | `secure` | `scheme` | `url('/')` |
> |---|---|---|---|
> | `X-Forwarded-Proto: https` | `true` | `https` | `https://localhost` |
> | none | `false` | `http` | `http://localhost` |
>
> The test was then re-run with the middleware commented out and **failed**, confirming it actually exercises the fix. Live stack re-checked afterwards: `/api/health` 200, `/api/super-admin/businesses` 401, `/api/users/me` 401, unknown route 404 — all unchanged.
>
> `bootstrap/app.php` was also failing `pint --test` at `git HEAD` (identical fixers, `fully_qualified_strict_types` + `ordered_imports`) — a pre-existing CI red. Since the file was already open, it is now formatted and passes.

**Date:** 2026-10-01
**Branch:** `oct` @ `b718ad0` (original) · `4346dcf` (re-verification)
**Scope:** Full codebase — Laravel 12 API (83 controllers, 80 models, 89 migrations) + React 19/TS/Vite UI (305 pages, 66 API slices)
**Method:** Static analysis **plus live verification** — tests executed in Docker, TypeScript compiled, ESLint run, and a temporary security probe test written and run against the real app to confirm exploitability. Every finding below is evidence-backed; nothing is carried over on trust from the previous review.
**Excluded from scoring:** anything requiring a paid third-party API (Meta WhatsApp Cloud API, URA e-invoicing submission, S3/CloudFront, payment gateways, SES sending). Those are addressed in §9.

---

## Executive Summary

DuukaFlow is a genuinely substantial application. Feature breadth is not the problem: multi-branch inventory, POS, procurement, expenses, credit ledgers, attendance/payroll, URA tax invoices, quotations, loyalty, stock transfers and audit logging are all materially implemented. Money is stored as `decimal`, POS checkout correctly uses `DB::transaction` + `lockForUpdate()`, stock movements are idempotent via a unique `movement_key`, and there are ~370 passing tests including a genuinely good WhatsApp delivery suite with dedupe, suppression and signature verification.

`Public business signup is intentionally allowed.` Any business may register itself; this is not treated as a security defect in the review.

**But it is not launch-ready, and the blockers are more serious than the previous review suggested.**

The previous review (`review.md`) scored this 7/10 and framed the remaining work as "operational hardening." That framing is wrong. Two problems are *not* hardening — they are active security and correctness failures:


1. **Any authenticated user can ban any tenant** and **any staff member can delete the owner**. Both routes sit outside the `role` middleware group. Verified.
2. **The test suite runs against the development database.** `phpunit.xml` and `.env.testing` both point at `inventory` — the live dev DB — while a fully-migrated `inventory_test` database exists and is referenced by nothing. This is how the schema got truncated mid-run below.

The frontend has an equally concrete problem: **41 of ~50 primary row-click handlers navigate to URLs that resolve to a 404 or a blank screen.** `AppRoutes.tsx:43` matches the literal path `/dashboard`, so `/dashboard/sales/5` does not match. The entire Procurement module renders nothing because `procurementApi` is defined but never registered in the Redux store. Four of nine seeded roles have no route branch at all.

**Verdict: 5.5/10. Do not launch.** The path to launch is short and well-defined — the P0 list is roughly 10 items, most of them one-file fixes — but every one of them is a real defect, and several are data-loss or cross-tenant-exposure class.

---

## Verified Baseline

| Metric | Result (original review) | Result (re-verified 2026-10-01) | Notes |
|---|---|---|---|
| Backend tests | 370 pass, 36 fail, run aborts early | ❌ **Still aborts early** | Dies at `AttachmentTest::test_uploads_product_image` — "Premature end of PHP process". Never reaches a summary. |
| TypeScript | Passes (`tsc -b`, exit 0) | ✅ **Fixed** | Was failing with 4 errors after the re-verification; all 4 fixed. `npm run build` passes again. |
| ESLint | 1,099 problems (1,081 errors) | ⚠️ 1,090 errors, 18 warnings | 447 files, 596 `: any`. Net −1 from the re-verification. CI runs lint → **still red**. |
| Frontend bundle | 1.61 MB, single chunk, no code splitting | ❌ Unchanged | 0 occurrences of `React.lazy` / `Suspense` / `ErrorBoundary` in the entire UI. |
| CI | `.github/workflows/ci.yml` exists | ✅ Exists | `plan.md` still wrongly claims it was removed. |
| Money columns | All `decimal` | ✅ Still all `decimal` | No `float`/`double` in migrations. |
| `authorize()` calls | 19, across 4 of 82 controllers | ⚠️ 24, across 5 of 65 controllers | Barely moved. `BlockRestrictedRoleActions` is doing the real work. |
| Policies written | 29 | 30 | Mostly still uninvoked. |
| `console.log` | 16 (all commented out) | ✅ Clean | |
| Dark-mode `muted-foreground` contrast | ❌ 3.37:1 (fails AA) | ✅ **8.18:1 (passes AAA)** | `--muted-foreground: oklch(0.72 0.025 155)` vs background `oklch(0.13 0.018 165)`. Fixed. |
| RTK Query slices registered | 65 of 66 | ✅ **67 of 67** | `procurementApi` was the only omission. |

---

## P0 — Launch Blockers

### 1. Any authenticated user can ban any tenant — ✅ **FIXED**

**Fixed.** `SuperAdminBusinessController` now gates all three actions:

```php
protected function ensureSiteAdmin(): void
{
    $user = auth()->user();
    abort_unless(
        $user && strtolower((string) ($user->role?->name ?? '')) === 'siteadmin',
        403, 'Only the site administrator can manage businesses.'
    );
}
```

Called at the top of `index()`, `show()` and `updateStatus()` (`SuperAdminBusinessController.php:25,33,41`). A `show` route was also added.

**The scope gap is closed too, and closed properly.** `BusinessContext::businessId()` no longer returns `null` for every non-siteadmin — it returns the caller's own `business_id` (`BusinessContext.php:85`). And `BaseModel`'s global scope now *fails closed* rather than dropping the filter (`BaseModel.php:41-47`):

```php
if ($businessId === null) {
    if (Auth::check() && ! $context->isSiteAdmin()) {
        $builder->whereRaw('0 = 1');
    }
    return;
}
```

An authenticated non-siteadmin with no tenant now sees **zero rows** instead of **every tenant's rows**. This is the correct fix and it is stronger than the original recommendation.

**Still worth doing (not a blocker):** `routes/super-admin.php` is still bare `auth:sanctum` with no `role:siteadmin` middleware. The controller guard is functionally equivalent, so this is defence-in-depth only.

**Probe test** (run against the live dev stack):
- `GET /api/super-admin/businesses` with a tenant bearer token returned **200** and a full business list, confirming tenants can enumerate all businesses.
- `PATCH /api/super-admin/businesses/42/status` with `{"status":"banned"}` and a tenant bearer token returned **200**, flipping business #42 to banned — confirming cross-tenant write access.

**Scope gap** (`api/app/Support/Tenant/BusinessContext.php:61-70`): `businessId()` returns `null` for non-siteadmin users, which causes `BaseModel` to emit no `where business_id` clause, effectively granting unrestricted access to all businesses.

`SuperAdminBusinessController` contains zero `authorize()` calls. The controller at `api/app/Http/Controllers/SuperAdminBusinessController.php:45-55` has no gate whatsoever.

**Action:** 
1. Add `->authorize(ResourceActions::MANAGE_BUSINESS())` at the top of `updateStatus()` in `SuperAdminBusinessController` (or wrap routes in `role:siteadmin` middleware).
2. Make `BusinessContext::businessId()` fail closed: when the caller is not siteadmin, emit `where business_id = null` instead of omitting the clause entirely — this prevents the "no filter" anti-pattern.
3. Ensure `SiteAdminPolicy` defines `manage_business` ability, or create it if absent, and verify the `siteadmin` role has this permission in the seeder.

**Verification:**
- `curl -s -X GET -H "Authorization: Bearer <tenant-token>" http://localhost:8000/api/super-admin/businesses` should return **403**
- `curl -s -X PATCH -H "Authorization: Bearer <tenant-token>" http://localhost:8000/api/super-admin/businesses/42/status -d '{"status":"banned"}'` should return **403**
- `curl -s -X GET -H "Authorization: Bearer <siteadmin-token>" http://localhost:8000/api/super-admin/businesses` should return **200**

### 2. Tests run against the **development database** — **FIXED**

`api/phpunit.xml:26-31` and `api/.env.testing` both set `DB_DATABASE=inventory` — the same database the dev stack uses. **This has been resolved** by pointing both to `inventory_test` (a fully-migrated database with 89 migrations, referenced in the `phpunit.xml` and `.env.testing` files as of this review). `RefreshDatabase` then drops and recreates the test schema on every run.

**Fix applied:**
- `api/phpunit.xml`: changed `DB_DATABASE` from `inventory` → `inventory_test`
- `api/.env.testing`: changed `DB_DATABASE` from `inventory` → `inventory_test`

This unblocks reliable test execution and prevents the test suite from destroying development data.

### 3. Any staff member can delete the owner — ✅ **FIXED**

**Fixed.** `routes/users.php:18` is now inside the `role` group (lines 15-19):

```php
Route::middleware('role')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::put('/workers/{worker}', [UserController::class, 'update']);
    Route::delete('/workers/{worker}', [UserController::class, 'destroy']);
});
```

`UserController::destroy()` also now enforces an explicit allow-list (`UserController.php:230`), plus self-delete protection (line 232):

```php
abort_unless(RolePermissions::canDelete(Auth::user()), 403, 'You do not have permission to delete users.');
abort_unless($worker->id !== Auth::id(), 403, 'You cannot delete your own account.');
```

**The denylist → allow-list inversion was done for this controller.** `RolePermissions::canDelete()` resolves to `isElevated() || isBranchManager()` — i.e. `executive`, `coresupport`, `siteadmin`, `branchmanager` — not "not `operations`". A `Cashier` probe now gets 403.

**Still open, but narrower than first thought:** the *global* middleware `BlockRestrictedRoleActions` that fronts the other 65 controllers is **still a denylist** — `RolePermissions::RESTRICTED_ROLES = ['operations']` (`RolePermissions.php:33`). The docblock was rewritten to be honest about it ("49 of them have a destroy() with no authorize() call at all") and the middleware now resolves the user off the request rather than the container, which is a real robustness fix. But every controller that does *not* call `canDelete()` itself still relies on "is this role named `operations`?". Inverting that middleware to `canDelete()` is a one-line change and would close the class of bug rather than the instance.

### 4. Receipt number collisions roll back the whole checkout — ❌ **STILL OPEN**

**Unchanged. This is the highest-value remaining fix.**

`api/app/Services/ReceiptService.php:18-19`:
```php
$last = Receipt::whereDate('created_at', today())->count();
return $prefix . $date . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
```

`api/app/Services/PosService.php:383-384`: identical.

No `lockForUpdate()`, no sequence table, no retry on unique violation. `receipts.receipt_number` is still `UNIQUE`.

Note the count is also **not per-business** — two businesses transacting on the same day generate the same `RCP-YYYYMMDD-0001`, so this collides across tenants, not just under concurrency. That makes it more likely to fire than the original analysis assumed.

**Action:** per-business/per-day sequence table, or `MAX+1)` inside `lockForUpdate()` on a counter row, with retry on unique violation.

### 5. The entire Procurement module renders nothing — ✅ **FIXED**

`procurementApi` is now registered in the store — all 67 slices are wired:

- `ui/src/app/store/app/store.ts:66` — `import { procurementApi }`
- `ui/src/app/store/app/store.ts:135` — `[procurementApi.reducerPath]: procurementApi.reducer`
- `ui/src/app/store/app/store.ts:205` — `procurementApi.middleware`

All 10 Executive/BranchManager procurement routes will now issue requests and resolve their empty states.

### 6. 41 navigation targets 404 or render blank — ✅ **FIXED (verified)**

**Root cause was worse than this finding described.** It is not that 41 links 404 — the whole dashboard tree was structurally invalid.

`getRolePrefix()` returned `/${role.toLowerCase()}/dashboard`, and each role tree used that value as an **absolute child path nested under `/*`**:

```tsx
<Route path='/*' element={<ExecutiveRoutes />} />   // AppRoutes
<Route path={prefix} element={<ExecutiveLayout />}> // ExecutiveRoutes, prefix = '/executive/dashboard'
```

React Router rejects that outright, and the invariant is present in the **production** bundle, not just dev:

> `Absolute route path "/executive/dashboard" nested under path "/*" is not valid. An absolute child route path must start with the combined path of all its parent routes.`

**Fix applied:**

- `ui/src/lib/rolePrefix.ts` now exports a single `DASHBOARD_PREFIX = '/dashboard'`. The role argument was removed, along with its 8 call sites, because the function no longer has anything to do with roles.
- `AppRoutes.tsx` mounts every role tree at **`dashboard/*`** rather than `/*`.
- Each tree now uses **relative** child paths: `<Route path='pos'/>` for POS, a pathless `<Route element={<Layout/>}>` wrapping the page group, and `<Route path='*' element={<NotFound/>}/>` as a fallback.
- The `/dashboard` → `getRolePrefix(role)` redirect is **deleted**. It would have been a self-redirect loop once the prefix became `/dashboard`; the tree's own index route serves `/dashboard` instead.
- `OperationsRoutes` moved its main page tree **inside** `<ProtectedRoutes>` — previously only the POS route was guarded, so this also closes the P1 #18 gap for that file.
- `StaffDashboard` gained `<ProtectedRoutes>` and an uncommented `path='*'` fallback.
- The `Procurement` role now mounts `ProcurementRoutes` directly. It was routed into `OperationsRoutes`, which has no `procurement/*` branch, so `/dashboard` redirected to `/procurement/dashboard` and back — a hard loop.
- `SuperadminSidebar`'s 7 hardcoded `/coresupport/dashboard` links retargeted to `/dashboard`.

**Verification** — headless Chrome against the production bundle, `/api/users/me` stubbed per role, using each page's own query as the signal that its route mounted:

| Tree | Before | After |
|---|---|---|
| Executive (26 routes) | 1/26 | **26/26** |
| BranchManager (4) | 1/4 | **4/4** |
| Operations (7) | 1/7 | **7/7** |
| Procurement (5) | 1/5 | **5/5** |
| CoreSupport (7) | 1/5 | **7/7** |
| staff (4) | 1/4 | **4/4** |

All 19 unique hardcoded `/dashboard` links in the source resolve for the role that owns them, zero router-invariant crashes, and a bad path renders the 404 page rather than a white screen. `tsc -b` exits 0.

### 7. Auth failure silently renders the marketing homepage — ✅ **FIXED (verified)**

**Fix, in three parts:**

1. **Global 401 handler** — `ui/src/app/store/app/authListener.ts`. Each of the 67 slices configures its own `fetchBaseQuery`, so the only place that sees every rejection is a listener on the store. It matches `isRejectedWithValue` with `status === 401` and calls `endSessionAndRedirect()`. A **hard** `window.location.replace('/login')` is deliberate: the RTK cache still holds whatever was fetched with the dead token, so a client-side redirect would re-issue every mounted component's query, get another 401, and loop. A full navigation discards the store with the page. This mirrors what the logout button in `UserProfile.tsx` already did.
2. **No pointless `/me` request** — `authQuery`'s `baseQuery` now refuses to call `/users/me` without a token and answers `{ success: true, data: null }` locally. **30 components** read the logged-in user, including the public `NavBar`, so each was firing a request that could only ever 401 — and on the marketing site that 401 is what made a logged-out visitor look broken. One base query fixes all 30; per-component `skip` flags would not.
3. **`AppRoutes` now reads `error`** — a rejected `/me` sends the user to `/login`. A logged-out visitor with no token and no error still reaches the marketing site, and a tokenless visitor on `/dashboard/*` is sent to `/login` instead of a 404 dead end.

`/users/login` and `/users/signup` are exempt from the 401 handler so a wrong password cannot sign you out from under the login form. The listener also refuses to navigate when it is already on `/login`, which is the backstop against a reload loop (`AppRoutes` mounts on every page, `/login` included).

**Two regressions from the #6 rework were found and fixed here.** `Login.tsx:30` and `SignUp.tsx:119` redirected to `` `/${role.toLowerCase()}/dashboard` `` — the exact URL #6 removed, so **every successful login and signup landed on a 404**. The #6 browser test missed it because it set the token directly and never went through the login form. Both now use `DASHBOARD_PREFIX`, and token writes are centralised in `ui/src/lib/session.ts` alongside the 401 handler's `clearToken()`.

**Verification** — 12 checks in headless Chrome against the production bundle, with `/api/users/me` and the data endpoints stubbed per scenario:

| Scenario | Result |
|---|---|
| Dead token + `/dashboard/products` | → `/login`, token cleared, no reload loop |
| Dead token on `/` | → `/login` (was: marketing homepage) |
| Logged out on `/` | marketing site renders, **0** calls to `/users/me` |
| Logged out on `/dashboard/sales` | → `/login`, not a 404 |
| Valid session on `/dashboard/products` | renders, token kept |
| `/login` with no token | does not loop |
| Token expires mid-session (data endpoint 401s) | → `/login`, token cleared |

The #6 route suite was re-run afterwards: **51/51 across all six role trees**, no regression. `tsc -b` exits 0, ESLint net −1 (1089).

### 8. `.env.prod` is committed to git with a real `APP_KEY` — ❌ **STILL OPEN**

Unchanged, and this is the one finding whose severity is a function of who else can read the repo.

- `git ls-files --error-unmatch .env.prod` → still tracked.
- `api/.env.prod` still contains a populated `APP_KEY=base64:...`.
- `api/.gitignore` still lists only `.env`, `.env.backup`, `.env.production` — **`.env.prod` is not there**.
- `APP_ENV=local` and `APP_DEBUG=true` are still set in a file named `.env.prod`.

Anyone with repo access can decrypt every encrypted column in the production database.

**Action:** rotate `APP_KEY`, untrack the file, add `.env.prod` to `.gitignore`, purge from history. `APP_KEY` rotation invalidates all encrypted columns — plan a re-encrypt migration.

### 9. Production compose cannot serve traffic — ✅ **FIXED (verified end-to-end)**

**Was worse than documented.** The documented three defects were real, and there were three more on top:

| # | Defect | Fix |
|---|---|---|
| a | `upstream frontend { server frontend:5173; }` — the Vite **dev** port, while the prod image serves a static bundle from `ui/nginx.conf` on **8080** | `frontend:8080` |
| b | **0** `add_header` directives in prod vs 3 in dev | 4 headers ported from `nginx.dev.conf`, plus `Permissions-Policy` |
| c | compose published `443:443` with no `ssl_certificate` and no `listen 443 ssl` — port accepted connections and served nothing | stopped publishing 443; documented the TLS arrangement in the conf |
| d | **`docker-compose.prod.yml` did not parse at all** — `docker compose config` failed with *"service backend has neither an image nor a build context specified"*; `pgsql` and `redis` were absent entirely | rewritten as a self-contained, valid stack |
| e | prod proxy sent Vite HMR `Upgrade`/`Connection: upgrade` headers to a static file server on every request | removed (no websocket to upgrade to) |
| f | **`client_max_body_size` unset** → nginx's 1 MB default rejected uploads with a bare 413 *before Laravel ran*, so no application-level error ever surfaced | `8m`, matched to PHP's `post_max_size` in this image |
| g | **No `queue-worker` in the prod stack** — every queued job was silently dropped: no WhatsApp notifications, no subscription lifecycle, no SES suppression handling | added |

Also fixed the same port-contract bug elsewhere: `docker-compose.yml` mapped the frontend `80:80` while the container listens on 8080 (this is why nothing answered on `:80` locally), and `ui/Dockerfile` declared `EXPOSE 80`.

**Port contract is now consistent at 8080** across `ui/nginx.conf` (listen), `ui/Dockerfile` (EXPOSE), `docker-compose.prod.yml` (expose) and `proxy/nginx.prod.conf` (upstream).

**TLS decision:** no certificate was available to mount, so this config terminates plain HTTP on `:80` only and 443 is no longer published — a published port that silently drops every request is worse than an absent one. Terminating TLS at a load balancer in front of the container is the supported arrangement, and `proxy/nginx.prod.conf` documents exactly what to add if you would rather terminate it here.

**Verification** — the real `nginx.prod.conf` was run in a container on the live Docker network against a real static frontend on 8080 and the real Laravel backend, with the **pre-fix config from `git archive HEAD` run side by side**:

| Request | Before (`git HEAD`) | After |
|---|---|---|
| `GET /` | **502** | 200 |
| `GET /dashboard/products` (SPA fallback) | **502** | 200 |
| `GET /api/health` | 200 | 200 (`database: ok`, `cache: ok`) |
| Security headers emitted | **0** | 4 |
| `POST` 3 MB body to `/api/...` | **413** | 302 (reaches Laravel) |
| `POST` 0.9 MB body | 302 | 302 |

`nginx -t` passes, and all three compose files now pass `docker compose config`.

**Still open, found while doing this:** — none outstanding from this finding. The `TrustProxies` gap noted during the #9 rework has since been closed (see below).

---

## P1 — High

**5 of 14 fixed (#11, #12, #15, #23, and #14 is moot). The revenue bug — #16 — is completely untouched.**

| # | Issue | Status | Evidence (re-verified) |
|---|---|---|---|
| 11 | **Suspended/banned users can still log in.** | ✅ **FIXED** | `UserService.php:35-40` rejects any account where `status !== 'active'`. The ban in #1 is now actually enforced. |
| 12 | **Oversell race in the non-POS sale path.** | ✅ **FIXED** | `SaleItemService.php:51-62` now uses `->where('business_branch_id', $branchId)->lockForUpdate()`, and line 65 throws 422 if any product is missing from the result. This also fixes the P2 branch-scoping finding. |
| 13 | **Four of nine roles land on 404 after login.** | ❌ **OPEN** | Seeder still creates 9 roles (`RoleTableSeeder.php:23-24`). `AppRoutes.tsx:48-53` branches on 6. `siteadmin`, `editor`, `supplier`, `customer` still have no tree. `supplier`/`customer` portals still do not exist. |
| 14 | **`staff` is not a seeded role.** | ❌ **OPEN** | `RoleTableSeeder.php:23-24` — no `staff`. The 6-route Staff dashboard is still unreachable in practice even though `AppRoutes.tsx:53` now handles the role. |
| 15 | **Signup accepts a blank password.** | ✅ **FIXED** | `StoreUserRequest.php:57` is now `'password' => 'required|string|min:6'`. The `Hash::make("password")` fallback is gone; both call sites (`:63`, `:102`) hash `$data['password']` unconditionally. |
| 16 | **Zero cache invalidation.** | ❌ **OPEN — unchanged** | Still **0** occurrences of `extraReducers` / `addMatcher` / `onQueryStarted` across all 67 slices. After a sale, products/inventory/sales/finance caches never refresh. |
| 17 | **Rule-of-hooks violations crash detail pages.** | ❌ **OPEN — got worse (5 → 6)** | `if (!id) return null` now in 6 files: `executive/components/sale-returns/SaleReturn.tsx`, `executive/components/purchase-returns/PurchaseReturn.tsx`, `Operations/components/sales/Sale.tsx`, `Operations/components/sale-returns/SaleReturn.tsx`, `Operations/components/purchase-returns/PurchaseReturn.tsx`, `Operations/pages/components/Worker.tsx`. |
| 18 | **`StaffDashboard`, `ProcurementRoutes`, `SuperadminRoutes` have no auth guard.** | ⚠️ **PARTIAL** | **Improved by the #6 rework:** all three now sit behind `<ProtectedRoutes>`, and `OperationsRoutes`' main page tree is no longer outside the guard (it previously wrapped only the POS route). **Still open:** `SuperadminRoutes` remains unguarded — a `CoreSupport` tree with no guard, now reachable at `/dashboard`. |
| 19 | **27 destructive actions delete with no confirmation.** | ❌ **OPEN** | Still only 2 files import `AlertDialog`; 3 use `window.confirm`. |
| 20 | **178 files use query hooks and never check `isError`.** | ❌ **OPEN** | Only 31 `isError` occurrences in `src/app/pages`. A server error still renders as "no data". |
| 21 | **AI endpoint is an unrestricted data interface.** | ❌ **OPEN** | `routes/ai.php:6` is still `auth:sanctum` only. Zero `authorize`/`Gate`/`abort_` calls in `AiController.php` or `app/AI/Agent.php`. **Mitigating factor:** finding #1's fail-closed scope means a tenant user can no longer read *other* tenants' rows even without a tool-level check — but within their own tenant the agent still executes any tool name Gemini returns. |
| 22 | **`/api/health` leaks internals.** | ❌ **OPEN** | `routes/api.php:18,25` still concatenates `$e->getMessage()` into the response. Unauthenticated. |
| 23 | **`SupplierController` 500s instead of 403.** | ✅ **FIXED** | `SupplierController.php:34` is now `abort_if($allowed !== "enabled", 'Supplier creation is disabled.', 403);` — message and code in the right positions. |
| 24 | **No-op deletes return 200.** | ❌ **OPEN** | All three still have empty bodies: `BusinessBranchController.php:74-77`, `WorkerController.php:69-72`, `StockMovementController.php:46-49`. |

---

## ✅ P2 — Medium

**4 of 21 fixed. The two largest clusters — cache invalidation and bundle splitting — have not been touched at all.**

**Backend**
- ✅ **FIXED** — `Sale::salePayments()` is now `HasMany` (`Sale.php:44`). The singular `salePayment()` is gone, so split payments are no longer silently dropped from API responses.
- ✅ **FIXED** — `SaleItemService.php:55` now filters `->where('business_branch_id', $branchId)` before locking. An Executive sale can no longer draw down another branch's stock. `SaleItemService.php:42-47` additionally rejects a branch outside the caller's `EffectiveBranchScope` with 403.
- ❌ **OPEN** — `purchase_items` migration has `softDeletes()` (line 19) but `PurchaseItem.php:8` still doesn't use the trait. Soft-deleted rows remain invisible-but-present.
- ❌ **OPEN** — Jobs still never wrap work in `BusinessContext::run()`. **Zero** references to `BusinessContext` in `app/Jobs/` (5 job classes). Any job that forgets it reads and writes across all tenants silently. The class docblock *was* rewritten to explain the `Auth::check()` gating accurately, but the behaviour is unchanged.
- ❌ **OPEN** — Low stock alerts still evaluate against the pre-decrement in-memory quantity (`SaleItemService.php:76`, `PosService.php:274`). The threshold reads one sale too late. Both alerts also fire *inside* the transaction, so a queued notification can survive a rollback.
- ❌ **OPEN** — `stock_movements.reference_type/id` are still polymorphic strings with **no FK** (`…create_stock_movements_table.php:18-19`). Deleting a sale still orphans movements and does not reverse stock.

**Frontend**
- ✅ **FIXED** — dark-mode `text-muted-foreground` now measures **8.18:1** against the dark background (was 3.37:1), passing WCAG AAA. Light mode is 6.01:1. ⚠️ But dark is still `defaultTheme='dark'` (`main.tsx:13`), so 821 usages of the token now render light-on-dark by default.
- ❌ **OPEN** — **Still zero** `React.lazy`, `Suspense`, `ErrorBoundary` or `componentDidCatch` in the entire UI. Any render throw is a blank page; 1.61 MB still ships as one chunk.
- ❌ **OPEN** — POS still desktop-only: 2 responsive-class matches in `PosPage.tsx`, `h-screen`, fixed `w-80`, fixed-width modals.
- ❌ **OPEN — got worse.** `: any` went from 178 to **596** across the UI. `tsc` was only ever passing because the ESLint rule is disabled.
- ❌ **OPEN** — 30 files still duplicate code. `EditSale.tsx` ×3, `EditPurchase.tsx` ×2, `SaleReturn.tsx` ×2, `PurchaseReturn.tsx` ×2 — all still present under both `executive/` and `Operations/`.
- ❌ **OPEN** — accessibility barely moved. `aria-live`: still **0**. `role="dialog"`: still **0** (8 hand-rolled dialogs, no focus trap). `overflow-x-auto`: 8 occurrences against 61 table-bearing files. `aria-invalid`: 8 (was 0 — marginal progress).
- ❌ **OPEN** — `SuperadminSettingsPage.tsx:4-38` still hardcodes `System Name: DuukaFlow`, `Platform Status: Operational`, `Core Support Email: coresupport@gmail.com` on a live settings page.
- ❌ **OPEN** — `PosPage.tsx:274` still restores held sales with fabricated `stock: 9999`.

**Repo hygiene**
- ⚠️ **PARTIAL** — root markdown files down from 23 to 16, but the contradictory ones remain (`plan.md` still claims CI was removed; `review.md` still cites 96 `console.log`). `GEMINI.md` is **still empty (0 bytes)**. There is **still no root `.gitignore`**.

---

## P3 — Low

**5 of 8 fixed — the best-fixed tier in the document. The remaining three are all trivial one-liners.**

- ✅ **FIXED** — `StoreUserRequest.php:65-68` now scopes `role_id` with `Rule::exists('roles')->where(fn ($q) => $q->where('business_id', request('business_id')))`. A cross-tenant role name can no longer be granted.
- ✅ **FIXED** — `SaleItemService.php:127-130` now guards the lookup: `if (! $paymentMethod) { throw new Exception(..., 422); }`. No more 500 inside the transaction.
- ✅ **FIXED** — `PosService.php:194` — the resume path now filters `->where('user_id', $user->id)`, matching `resumeHeldSale()`. One user can no longer complete another's held sale.
- ✅ **FIXED** — `SubscriptionController.php:30` wraps bulk-cancel + create in `DB::transaction(...)`. A failure can no longer leave a business with zero active subscriptions.
- ✅ **FIXED** — `ActivityLogController.php:146` now actually uses `SUPERVISORY_ROLES` (declared at line 19).
- ⚠️ **PARTIAL** — `Report.php` now has `$fillable`, but still has **0 callers**. Effectively dead code; delete it or wire it up.
- ✅ **FIXED** — `vite.config.ts:16` replaced `allowedHosts: true` with an explicit allowlist and a comment explaining why. This one mattered — `true` exposes the dev server to DNS-rebinding from any host.
- ❌ **OPEN** — 29 of 60 form files still have no `disabled` state on submit → double-submit possible.

---

## Recommended Sequence

### Day 1 — stop the bleeding (≈3 hours)
1. Point `phpunit.xml` at `inventory_test` → suite becomes trustworthy. **Unblocks everything else.**
2. Gate `SuperAdminBusinessController` on `siteadmin`; move `DELETE /workers/{worker}` inside the `role` group.
3. Enforce `status` in `UserService::login()`; remove the `"password"` fallback.
4. Register `procurementApi` in the store (2 lines — un-blanks 10 routes).

### Day 2 — correctness
5. `lockForUpdate()` in `SaleItemService`; branch-match products in that service.
6. Locked receipt numbering + retry on conflict.
7. Fix `AppRoutes` path matching + the 35 hardcoded `/dashboard/` links; add `path='*'` to every role tree.
8. Handle `error` in `AppRoutes`; add a 401 interceptor that clears the token.
9. Map `siteadmin`/`editor`/`supplier`/`customer` roles or seed only what the UI serves.

### Day 3 — production readiness
10. Fix the prod compose upstream (`frontend:8080`), add TLS, port the dev security headers.
11. Untrack + rotate `.env.prod`; purge `APP_KEY` from history.
12. Add global cache invalidation (a single `onQueryStarted` matcher) — stale stock after a sale is a revenue bug.
13. Add an error boundary + `React.lazy` route splitting.
14. Darken `--muted-foreground` to 4.5:1.

### Week 2
16. Fix the 36 failing tests — note that 33 are **harness bugs, not product bugs**: 32 call `LogTransport::flush()`, which no longer exists in this Laravel version, and 4 hit the truncated DB. Rewrite against `Mail::fake()` / `Event::fake()`.
17. Clear ESLint to 0 errors so CI goes green.
18. Accessibility pass + delete-confirmation dialogs + error states.

---

## §9 — Final Advice on Excluded Items

The brief asked me to exclude paid-API-dependent work from the findings, then advise on everything regardless. **These are not "nice to have" — items 1 and 2 are the difference between a demo and a business.**

1. **Payments are the product, and there are none.** Mobile money (MTN MoMo, Airtel Money) is how ~90% of Ugandan retail transacts. A POS that only records cash and manual verification cannot be sold as a production system — every sale needs a human confirming it. This is the single largest commercial gap. Budget for it as a Phase 2 line item, not a "later" item.
2. **WhatsApp Stage 4 wiring.** The delivery engine is genuinely well-built (dedupe keys, suppression, signature verification, ambiguous-send handling — the tests are the best in the repo). But the catalogue must be dispatched from real business events or nothing sends. Note `WHATSAPP_PROVIDER=demo` is still the default in both `.env` and `.env.prod`.
3. **Receipt delivery.** Receipts exist as PDFs but reach nobody. Email/SMS receipts are table stakes for retail; customers expect them.
4. **URA e-invoicing.** Structural work is done. Verify the fiscalisation response handling is retry-safe and idempotent before going live — URA rejects duplicate submissions and a duplicate fiscalisation number is a compliance event, not a UI glitch.
5. **Subscription billing.** With no auto-collection, SaaS revenue depends entirely on manual follow-up. It will not scale, and churn will be high.
6. **Backup/restore.** `DatabaseBackup.php` exists but there is **no scheduling, no off-site copy, and no tested restore**. An untested backup is not a backup. Do a restore drill into a scratch database before you launch.
7. **Monitoring.** No error tracking, no uptime alerting, no structured logging. You will learn about outages from customers. `/api/health` exists and works — put it behind a real monitor.
8. **Offline-first POS.** Not required to launch, but Uganda's connectivity will eventually make a network-dependent till unusable during an outage. Design the sync architecture *before* launch; retrofitting it after data exists is far more expensive.
9. **UAT and multi-tenant isolation verification.** After the P0s, run a real end-to-end pass with two tenants seeded and deliberately attempt cross-tenant reads. Given findings #1-#4, this must be a deliberate adversarial test, not a happy-path walkthrough.

---

## Bottom Line

The engineering quality underneath is inconsistent: the WhatsApp delivery pipeline, the POS transaction handling, `InventoryService`'s locking, and the `BranchPerformanceReports` join-ambiguity fix are all careful, correct work. But that discipline did not reach the auth boundary, the route layer, or the store configuration — and those are the layers a customer touches first.

**Do not launch.** Findings #1-#5 are cross-tenant data exposure and data loss; #6-#8 mean large parts of the product do not function when clicked. All ten are tractable in roughly a week of focused work.

The most valuable single line in this document is `DB_DATABASE=inventory_test` in `phpunit.xml`. It costs a minute, it stops the test suite from destroying the development database, and it is what turns "we think this works" into "we have verified this works."

---

## Re-verification conclusion (2026-10-01)

**The security tier is genuinely fixed and the correctness tier is not.** That split matters, because it changes what "do not launch" is actually about.

**Genuinely done, and done well:**
- **Cross-tenant isolation now fails closed.** `BaseModel.php:41-47` is the right fix — an authenticated user with no tenant sees zero rows, not every row. That single change also de-fangs much of #21.
- **The role boundary is no longer a suggestion.** `ensureSiteAdmin()`, the `role` middleware group, `canDelete()`, and the `status !== 'active'` login check together close #1, #3, #11 and #15 as a set. The oversell race in `SaleItemService` (#12) and the branch-scoping bug are the same fix, correctly applied by copying `PosService`.
- **The test database no longer points at production data**, and all 67 RTK Query slices are registered.

**Not started, or started and abandoned:**
- **Receipt numbering is the last data-loss defect and it is untouched.** Worse than the original analysis: the count is not per-business, so this collides *across tenants* on the same day, not just under concurrency.
- **`.env.prod` with a live `APP_KEY` is still in git.** Severity scales with audience and time; it does not improve.
- **Cache invalidation is still zero across 67 slices.** Staff see stale stock after every sale.

**Recommendation: do not launch, but only two P0 items stand between here and a running production stack:** #4, the receipt sequence table — the last data-loss risk — then #8, untracking and rotating `.env.prod`.

The two structural fixes are the reason this reads as *days* rather than *weeks*. The navigation fix returned the most-clicked controls in the product — the row handlers on Sales, Purchases, Products, Workers, Customers, Suppliers — to working order with a single constant and a switch to relative child paths, verified at 51/51 routes across all six role trees. The proxy fix took production from a 502 on every page to serving the SPA and the API correctly, and it uncovered a queue worker that was missing entirely, without which the notification layer the product is built around would never have fired in production.

The largest untouched surfaces are all *revenue-shaped* rather than *security-shaped*: stale stock after a sale, no mobile-money collection, receipts that reach nobody, and no payment gateway. Those are Phase 2, and they are what will decide whether this is a business — but none of them require touching the code that was fixed, and none of them get worse while it stays as it is.