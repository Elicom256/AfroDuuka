# Next — morning handoff

**Written:** 2026-10-02, end of session. Branch `oct`.
**Live stack:** all 6 containers healthy. API verified working through the proxy.

Read this alongside `checked.md`, which has the full P0–P3 audit with per-item evidence.
`checked.md` is the source of truth for findings; this file is what to *do next*.

---

## Read this first: 3 things that will waste your morning if you don't know them

### 1. The test suite now runs. It never did before.

```bash
docker exec duukaflow-backend-1 php vendor/bin/phpunit
```

Three separate things were broken, all found on 2026-10-02:

- **`phpunit.xml`'s `<env>` block was inert.** PHPUnit does not override a variable that
  already exists, and `docker-compose.dev.yml` hands the backend `APP_ENV`/`DB_*` from
  `api/.env`. So every line in `<php>` was silently ignored: the suite booted as
  `APP_ENV=local` against the **development `inventory` database**. The original review's
  "fix" for this (editing `DB_DATABASE` in `phpunit.xml`) changed nothing.
  Now fixed with `force="true"` plus a `<server>` mirror — Laravel's env repository reads
  `$_SERVER` and prefers it, and `<env>` alone does not populate that.
- **`DB_HOST` was `localhost`**, unreachable from inside the container. Now `pgsql`.
- **`memory_limit` was the container default (128M).** dompdf rendering a report exhausted
  it and PHPUnit reported `Premature end of PHP process`, which reads as a crash in
  whatever test happened to be running. Now `1500M` in `phpunit.xml`.

Current result: **501 tests, 43 errors, 17 failures.** Before today it aborted at test ~18
with no summary at all.

### 2. Those 60 failures are test-isolation defects, not product bugs

The dominant cause: `Business::factory()` creates a `Country` and a `BusinessCategory` from
fixed lists, and **both tables have UNIQUE names** (`countries.name`, `countries.iso_alpha2`,
`business_categories.name`). Tests that use `RefreshDatabase` get rolled back; tests that
don't leave rows behind that break every later factory call.

`PosCheckoutTest` alone fails 10/10 for this reason, and it passes the receipt-numbering
work fine.

**Two ways forward, pick one:**
- Make the factories idempotent (`Country::factory()` keyed on a stable name), or
- Have CI `migrate:fresh` the test DB before running, and stop relying on `RefreshDatabase`
  for isolation.

Until this is fixed, treat the failure count as noise and judge changes by
"did the number of failures go up".

### 3. Do not run `docker network disconnect` on this stack

I did that to force an IP change while testing the nginx DNS fix. It unregistered
`backend` from Docker's embedded DNS, and every API call 502'd until I recreated the
container. Cost about 15 minutes. `frontend` kept resolving the whole time, which makes it
look fine when it isn't.

---

## Uncommitted work

`git status` is dirty — **review and commit this.**

```
 M api/app/Support/Tenant/BusinessContext.php      the recursion fix + memoisation
 M api/phpunit.xml                                  force="true", <server> mirror, memory_limit
 M proxy/nginx.dev.conf                             resolver + variable proxy_pass
 M proxy/nginx.prod.conf                            resolver + variable proxy_pass
 D api/tests/Feature/RecursionProbeTest.php         debug scaffold, should not have been committed
?? api/tests/Feature/BusinessContextRoleLookupTest.php   real regression test
```

### Heads-up: two commits contain debug scaffolding

`8fd90b4`, `60456b3` and `6a56d2a` are my work that you committed. `RecursionProbeTest.php`
and a `public static int $probe` guard inside `BusinessContext.php` were live in the tree at
that moment and got swept in. Both are removed from the working tree. My fault for
instrumenting production code in place rather than on a branch — you may want to
`git rebase -i` and drop the stray file, or leave it since the next commit deletes it.

---

## Done and verified (don't redo these)

| # | Finding | Evidence |
|---|---|---|
| P0 #1 | Any user can ban any tenant | `ensureSiteAdmin()` on all 3 actions; `BaseModel` fails closed with `whereRaw('0 = 1')` |
| P0 #2 | Tests ran against the dev DB | **Fixed properly today** — see "Read this first" #1. Was *not* actually fixed before. |
| P0 #3 | Staff can delete the owner | Route inside the `role` group; `canDelete()` allow-list |
| P0 #4 | Receipt number collisions | Native Postgres sequence. Old algorithm demonstrably gave two businesses the same number. |
| P0 #5 | Procurement module blank | Reducer + middleware registered; 67/67 slices |
| P0 #6 | 41 nav targets 404/blank | Role trees at `dashboard/*`, relative child paths, `DASHBOARD_PREFIX`. **51/51 routes verified in Chrome.** |
| P0 #7 | Auth failure renders homepage | Global 401 listener + base query guard. **12/12 browser checks.** |
| P0 #9 | Prod compose can't serve | 7 defects. `GET /` **502 → 200** against a live nginx. |
| — | `TrustProxies` missing | Registered; verified `secure`/`scheme`/`url()` both directions |
| — | nginx upstream IP caching | Fixed today in **both** dev and prod confs — see below |
| — | `businessId()` infinite recursion | Fixed today — the most serious bug found |

## Open, in priority order

### 1. P0 #8 — `.env.prod` is committed with a real `APP_KEY`
Still tracked, still not in `.env.gitignore`. Rotate the key, untrack, purge from history.
`APP_KEY` rotation invalidates every encrypted column, so plan a re-encrypt migration.
Smallest task on this list; do it first to get it off the board.

### 2. Prod compose never exercised end to end
I verified `nginx.prod.conf` in isolation against a real static frontend and the real
backend, but the prod stack itself (`docker-compose.prod.yml`) has never been brought up.
It is now a valid, complete file — that's untested.

### 3. Cache invalidation is still zero
No `extraReducers` / `addMatcher` / `onQueryStarted` in any of the 67 RTK Query slices.
After a sale, staff see stale stock. **This is revenue-shaped and it's the biggest untouched
product gap.** A single `listenerMiddleware.startListening` with an `invalidatesTags`
matcher is the intended fix — the listener middleware added for the 401 handling is
already the right place for it.

### 4. Remaining P1s
- **#13/#14** — 4 of 9 seeded roles have no route tree; `staff` isn't seeded at all.
- **#16** — see above.
- **#18** — `SuperadminRoutes` still has no `ProtectedRoutes` wrapper.
- **#19** — 27 destructive actions delete with no confirmation (2 use `AlertDialog`).
- **#21** — `routes/ai.php` is `auth:sanctum` only; `AiController` has no authorization.
- **#22** — `/api/health` still returns raw `$e->getMessage()`.
- **#24** — 3 `destroy()` methods with empty bodies still return 200.

### 5. Frontend debt
- `React.lazy` / `Suspense` / error boundary: **zero** occurrences. 1.61 MB single chunk.
- ESLint: **1089 errors**, CI is red. Mostly `no-explicit-any` (596).
- 6 rule-of-hooks violations (`if (!id) return null` before query hooks).
- `migrate:fresh` is **not** idempotent — re-running the receipts index migration fails
  because the old index is already gone. Only matters for already-migrated databases.

---

## Two bugs found today that are worth understanding

### `BusinessContext::businessId()` recursed into itself

```
businessId()
  └─> Role::query()->whereKey(...)->value('name')      ← roles HAS a business_id
        └─> BaseModel 'business' global scope
              └─> businessId()   → ...
```

The scope needed the tenant id in order to build the scope that was asking for it. Because
`roles` carries a `business_id`, the `Role` query re-entered the scope, and each level
appended another `where` binding until the process exhausted memory.

**Any authenticated request that touched a tenant table died.** This was almost certainly
the real cause of `AttachmentTest` killing the suite, and it is why `businessId()` sat
behind a `try { }` in the committed version.

Fixed by reading the role name with `withoutGlobalScopes()` — a global scope must never
query a model carrying that same scope. Also memoised against the user id, because it used
to add a `SELECT` to *every* tenant query on every request.

Regression test: `tests/Feature/BusinessContextRoleLookupTest.php` (6 tests). It OOMs on
the old code, which is how you know it catches it.

### nginx cached the backend's IP across a container restart

```
upstream: "http://172.18.0.6:8000/api/users/me"    ← stale
```

An `upstream` block resolves once at startup. The backend came back on `172.18.0.3`, nginx
kept dialling `172.18.0.6`, and every API call 502'd. **This bit you directly** — the browser
got a 502 HTML page from `/api/users/me`, which the new `error` branch in `AppRoutes`
correctly treated as a dead session and redirected to `/login`. That's the "everything takes
me to login" symptom.

It's a production bug too: the first deploy that replaces the backend container takes the
whole API down until nginx is restarted. Both confs now use `resolver 127.0.0.11 valid=10s`
with the host in a variable, so `proxy_pass` re-resolves per request.

**Note the coupling:** P0 #7 made a previously-silent failure visible. That was the right
call, but it means infra breakage now looks like an auth problem. Worth remembering before
you debug the next "login is broken" report.

---

## Morning checklist

- [ ] Decide on the 60 test failures: fix the factories, or `migrate:fresh` in CI
- [ ] P0 #8 — rotate `APP_KEY`, untrack `.env.prod`
- [ ] Bring the prod stack up once: `docker compose -f docker-compose.prod.yml up -d --build`
- [ ] Cache invalidation (#16) — the largest revenue-shaped gap left
- [ ] Consider `rebase -i` to drop `RecursionProbeTest.php` from the committed history
