# Frontend Auth/Routing Audit — Findings and Plan

**Date:** 2026-10-04
**Scope:** `ui/` — why every route redirects to `/login`.
**Status:** audited by reproduction, not by reading. Root cause found and confirmed.

---

## 1. The symptom, reproduced

Reported: every route takes the user to login — the home page, pricing, everything.

Reproduced in Chromium against the running dev stack (`nginx` → `frontend:5173` → `backend:8000`):

| Scenario | Result |
| --- | --- |
| Clean visitor, no token, `/` | Renders the marketing page correctly. No login form. |
| Clean visitor, no token, `/login` | Renders the login form. |
| **Dead token in `localStorage`, `/`** | **`/login`** |
| **Dead token, `/pricing` `/about` `/documentation` `/terms` `/privacy`** | **`/login` — all five** |
| **Dead token, revisit `/` `/pricing` `/about` in sequence** | **`/login` every time. Never recovers.** |
| Valid session (Executive login) | `/dashboard`, `/`, `/pricing`, `/about`, `/documentation` all render. Correct. |

So the app is not broken for everyone. It is broken for anyone carrying a token the
server no longer accepts — and it never recovers on its own.

---

## 2. Root cause

### 2.1 The 401 handler is dead code

`src/app/store/app/authListener.ts` defines a listener middleware whose entire job is to
turn any 401 into a signed-out session: clear the token, then hard-navigate to `/login`.

**It is never imported.** A repo-wide search for `authListener` returns four hits: two in
its own file, and two inside a *comment* in `AppRoutes.tsx`. The store's `middleware`
chain in `src/app/store/index.ts` concatenates 70-odd API middlewares and does not include
it. So nothing in the running application ever clears a dead token.

That comment at `AppRoutes.tsx:71` is load-bearing and wrong:

> `authListenerMiddleware` clears the token on this same 401: once that has happened the
> error left in the query cache is stale…

It does not clear anything. The code below it reasons about a world where the token is
already gone.

### 2.2 The fallback redirect has no public-route exemption

With the listener inert, a stale token makes `hasToken` permanently true. Then
`AppRoutes.tsx:74`:

```tsx
if (hasToken && error && isAuthFailure(error) && !onLogin) {
  return <Navigate to='/login' replace />;
}
```

The only exemption is `!onLogin`. `/`, `/pricing`, `/about`, `/documentation`, `/terms`,
`/privacy` are not exempt, so any of them renders `<Navigate to='/login'>` instead. The
guard runs *above* `<Routes>`, so it replaces the whole page — which is why the marketing
site does not merely lose its navbar, it becomes unreachable.

### 2.3 The public site fires an authenticated request on every page

`/users/me` is requested by `AppRoutes` itself (line 49) and again by `NavBar` (line 20),
and `NavBar` is mounted on **every non-dashboard page** (`App.tsx:24`). So the trigger is
not incidental — on a public page with a token present, the app always asks the server who
you are, and a stale answer always means "go to login".

`authBaseQuery` already short-circuits this correctly when there is *no* token
(`authQuery.ts:34`), which is exactly why a clean visitor never sees the bug.

### 2.4 How you get a dead token — and it is easy

**Logging out does it.** `UserProfile.tsx:35`:

```tsx
return (window.location.href = '/login');
```

No `clearToken()`. The server revokes the token; `localStorage` keeps it. Reproduced:

```
logged in, token: 3|XellSwiH4Etu…
after logout url:        http://localhost/login
token AFTER logout:      3|XellSwiH4Etu…   <-- STILL PRESENT
   after logout, /            -> /login  <-- BOUNCED
   after logout, /pricing     -> /login  <-- BOUNCED
   after logout, /about       -> /login  <-- BOUNCED
```

So: log out, then try to read your own pricing page. Permanently bounced. This needs no
expiry and no tampering — it is one click on the app's own logout button.

`config/sanctum.php:50` has `'expiration' => null`, so tokens do not expire on their own.
That makes the bug *more* durable, not less: the only ways a token dies here are an
explicit revoke, a re-seed or `migrate:fresh` invalidating `personal_access_tokens`, or the
logout path above.

---

## 3. The full causal chain

```
1. User logs out  →  UserProfile.handleLogout() never calls clearToken()
                    (or: DB re-seeded, so an old token is now invalid)
2. Dead token sits in localStorage forever, because authListenerMiddleware
   is never registered in the store, so no 401 ever clears it
3. User opens /  →  AppRoutes + NavBar both call /users/me
4. Server answers 401
5. authListenerMiddleware does not fire (dead code)
6. AppRoutes:74  →  hasToken is still true, error is 401, not onLogin
7. <Navigate to='/login'> replaces the entire page
8. Repeat on every subsequent page. No self-recovery. No message explaining why.
```

---

## 4. Secondary findings

**4.1 `session.ts` documents behaviour that does not exist.** Its comment on
`endSessionAndRedirect` says *"This mirrors what the logout button in UserProfile.tsx
already does."* `UserProfile` does no such thing — it is the bug in 2.4. Both comments
should be corrected; one of them is load-bearing for the next person who reads this.

**4.2 "Start Free Trial" is a login wall.** `NavBar:82` sends logged-out visitors to
`/onboarding`, which renders a login form rather than a signup flow (`NavBar:75` links the
same button to `/login`). The primary marketing CTA cannot be completed by the people it
is aimed at. `/signup` exists and is not linked from the navbar at all.

**4.3 `role === 'staff'` has no matching role.** `AppRoutes:140` mounts `StaffDashboard` for
role `staff`. The `roles` table contains `Executive, BranchManager, CoreSupport, siteadmin,
Operations, Procurement, customer, editor, supplier` — no `staff`. That branch is
unreachable, and `StaffDashboard` plus its sidebar/attendance components are dead weight.

**4.4 `editor`, `supplier`, `customer` land on the 404 page.** Real seeded roles with no
`/dashboard/*` tree, so a user holding one who navigates to `/dashboard` gets `NotFound`
rather than a message. `hasToken` is true so they are not redirected to login — they are
simply lost. Worth deciding deliberately: either give them a tree or send them somewhere
honest.

**4.5 Role-name casing is a live fragility.** `AppRoutes` compares raw strings
(`role === 'Executive'`). The backend has already been bitten by this — `RolePermissions`
normalises with `preg_replace('/[^a-z0-9]/', '', strtolower(...))` precisely because role
names are stored inconsistently, and its docblock says so. Today's seed data matches the
frontend exactly, so it works. Any seed, import or tenant-created role using different
casing silently produces a 404 instead of a dashboard. The frontend has no equivalent
normalisation.

**4.6 No frontend tests at all.** `package.json` has `dev`, `build`, `lint`, `preview` and
no test script, no runner, no test files. Every bug in this document is a one-line
regression that a single test would have caught — particularly "a public route renders for
a visitor holding a stale token".

`tsc -b` passes clean. Lint remains red (1,080 errors / 18 warnings per `review.md`),
unchanged and out of scope here.

---

## 5. Plan

Ordered so the site is reachable first and the durability problems follow.

**Step 1 — register the listener (one line).** Add
`authListenerMiddleware.middleware` to the store's `middleware` chain in
`src/app/store/index.ts`. It already does the right thing; it is simply never switched on.
Expect the redirect loop to stop, since the token now actually gets cleared.

**Step 2 — stop the redirect from firing on public routes.** `AppRoutes:74` must not treat
a dead token as a reason to leave a public page. A stale token on `/pricing` should render
`/pricing` with signed-out chrome, which is what a visitor expects and what the navbar
already renders when `data?.data?.role` is undefined. Gate the redirect on the same
`isDashboardPath()` test already used at lines 84 and 99, rather than on `!onLogin`.

Do this **together with** step 1, not after. Step 1 alone stops the infinite loop but
still bounces a public page once on every load; step 2 alone still leaves the token stuck.

**Step 3 — clear the token on logout.** `UserProfile.handleLogout` must call `clearToken()`
in a `finally`, so it happens whether or not the server call succeeds. A failed logout that
leaves a revoked token in storage is the same bug by another route. Then fix the comment in
`session.ts` that claims this already happens.

**Step 4 — do not ask who you are on a public page.** The cleanest structural fix: give
`useLoggedinUserQuery` an `enabled` flag driven by `hasToken`, and have `NavBar` skip the
query entirely when there is no token. `authBaseQuery` already answers "no session"
locally, so nothing should need the round trip. This removes the trigger rather than
handling it, and it stops a public page from depending on auth at all.

**Step 5 — one honest definition of "public route".** `isDashboardPath` is a prefix test
against `/dashboard`, which means the answer to "may this page be seen without a session"
is currently inferred from a URL prefix. Introduce an explicit list of public paths and use
it for the redirect decision in step 2, the query decision in step 4, and the navbar. One
list, three consumers, no fourth place to forget.

**Step 6 — fix the marketing CTAs.** Point "Start Free Trial" at `/signup`, and link
`/signup` in the navbar next to "Log in". Decide whether `/onboarding` is reachable by a
logged-out visitor at all; if it is, it should be a signup funnel, not a login form.

**Step 7 — resolve the dead role branches.** Either add a `/dashboard/*` tree for `editor`,
`supplier` and `customer`, or send them somewhere honest instead of `NotFound`. Delete
`role === 'staff'` and `StaffDashboard` if `staff` is not a real role.

**Step 8 — normalise the role name on the frontend,** mirroring `RolePermissions.roleName()`.
Prevents finding 5 from becoming finding "a whole tenant gets a 404 after an import".

**Step 9 — add a frontend test runner** (Vitest + Testing Library) and write the regression
tests for this class of bug first, before any of the steps above land:

- every public route renders for a visitor holding a stale token
- every public route renders for a visitor with no token
- a public route is never redirected to `/login`
- logging out clears `localStorage.token`, even when the logout request fails
- a valid session still reaches `/dashboard`, per role

Step 9 is listed last because it is the one that stops the next person from spending a day
on this. Everything above it is a fix; that one is the difference between a fix and a
guarantee.

---

## 6. Notes for whoever picks this up

- Reproduce with Playwright (`/usr/bin/google-chrome`, `--no-sandbox`). The `playwright`
  package is not in `ui/node_modules`; it resolves from the npx cache, and its bundled
  chromium build is missing, so pass `executablePath`.
- Run the app through `http://localhost` (the nginx edge), not `:5173` directly — the Vite
  server sets `hmr.clientPort: 80` and the API base URL is `http://localhost/api`.
- Seeder credentials for a valid session: `executive@gmail.com` / `password`.
- `sanctum.expiration` is `null`, so "wait for the token to expire" is not a way to
  reproduce this. Use a dead token in `localStorage`, or log out and then browse.
- The two comments at `AppRoutes.tsx:64-73` describe the intended behaviour and are the
  best available spec for what step 1 and step 2 are supposed to make true. Read them
  before changing the code — they are right about the intent and wrong about the present.