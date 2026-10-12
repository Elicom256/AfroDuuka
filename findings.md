# AfroDuuka — Pre-Launch Code Review

> Reviewed: 2026-10-12 · Branch: `oct`
> Task: `undone.md`
> A separate, earlier review of this codebase is preserved in `findings-initial-review.md`.

**Excluded from scope** (per `undone.md`): WhatsApp integration, Email integration,
URA (EFRIS) integration, Auto payments / payment gateways.

Where a finding turned out to be wrong against the code, the deviation is recorded
rather than quietly dropped — see §12 and §2.7.

**Status key**: ✅ done · ⚠️ done, deviated from as written · ⬜ not started

---

## Status at a glance

| # | Section | Priority | Status |
|---|---------|----------|--------|
| 1 | Security — Critical | P0 | ✅ |
| 2 | Authentication & Authorization Gaps | P0 | ✅ |
| 3 | Error Handling | P0 | ✅ |
| 4 | Data Integrity & Race Conditions | P1 | ⬜ |
| 5 | Input Validation Gaps | P2 | ⬜ |
| 6 | Performance — N+1 Queries & Unpaginated Endpoints | P1 | ⬜ |
| 7 | Code Quality — Dead Code, Duplication, Large Methods | P2 | ⬜ |
| 8 | Frontend — State Management, Bundle, Types | P1/P2 | ⬜ |
| 9 | DevOps & Configuration | P2 | ⬜ |
| 10 | Testing Gaps | P2 | ⬜ |
| 11 | Modules to Add | P2 | ⬜ |
| 12 | Modules to Remove Before Launch | P2 | ✅ |

### Recommended tackle order

1. §1 Secrets · 2. §2 Authorization · 3. §3 Error handling · 4. §4 Race conditions ·
5. §12 Dead code · 6. §5 Validation · 7. §6 Performance · 8. §7 Code quality ·
9. §8 Frontend · 10. §9 DevOps · 11. §10 Testing · 12. §11 Modules to add

### Test counts

Recorded so a future drop is attributable.

| Point | Backend | Frontend |
|-------|---------|----------|
| After §1 | 862 passed | 756 (per project docs) |
| After §2 | 862 passed | — |
| After §3 | 868 passed (+6 new) | — |
| After §12 | 868 passed | 808 passed |

The 809 → 808 frontend drop is `themeContrast.test.ts`, which generates one case per
`.tsx` file it walks — deleting `ToastComponent.tsx` removed exactly one.
`AttachmentTest` and `TenantIsolationTest` have each flaked once in a full run and
passed on re-run: pre-existing ordering sensitivity, not introduced here.

---

# 1. Security — Critical ✅

### 1.1 Secrets committed to repository ✅
- **What**: `api/.env`, `api/.env.render`, `api/.env.testing` held a live `APP_KEY`,
  `GEMINI_API_KEY`, database passwords and a full Neon connection string. The root
  `.gitignore` ignored only `renderenv.md`.
- **Why**: Repo access meant decryptable sessions, database access and a spendable
  Gemini key. A live breach, not a hypothetical.
- **How**: Rotated concern is manual (below). `.env` / `.env.*` added to `.gitignore`
  with `.env.example` and `.env.production.example` negated; history purged with
  `git-filter-repo --invert-paths`.

> ⚠️ **Still requires a manual step.** The purged credentials were real and must be
> rotated at their providers — Laravel `APP_KEY`, database password, Gemini API key,
> Neon connection string. Rewriting history does not un-leak a credential that was
> already pushed.

> **Follow-up**: `git filter-repo` rewrote every commit hash, so `main` had to be
> force-updated to match (`git push origin oct:main --force`) before the PR stopped
> reporting conflicts. All pre-rewrite local branches were deleted.

### 1.2 `APP_DEBUG` in production ✅
- **What**: `docker-compose.yml` already set `APP_DEBUG: 'false'` on backend,
  queue-worker and scheduler; `.env.production.example` did not mention it.
- **Why**: Debug on in production leaks stack traces, paths and schema.
- **How**: Documented in `.env.production.example`; noted in `api/.env.example` that
  local dev keeps `true` and production must not.

### 1.3 Stack trace leakage via `$e->getMessage()` ✅
- **What**: 49 call sites across 13 controllers returned `$e->getMessage()` in JSON.
- **Why**: Names the failing table, column and constraint, and with debug on, file paths
  and line numbers.
- **How**: Global renderer in `bootstrap/app.php`; every call site replaced with a
  generic message and `report($e)`.

> **Regression caught here.** The first cut registered a blanket `\Throwable` renderer.
> It matches *before* Laravel's own handlers, so every `abort_unless()` 403 and every
> 422 came back 500 — policy refusals began reading as server faults. It is now scoped
> to genuine unexpected failures and explicitly defers `HttpExceptionInterface`,
> `ValidationException`, `HttpResponseException` and `ModelNotFoundException`.
> `TenantIsolationTest` caught this.

### 1.4 Token storage in localStorage ⚠️ interim only
- **What**: Tokens live in `localStorage`, readable by any script on the page.
- **Why**: One XSS is a full account takeover.
- **How (done)**: `SecurityHeaders` middleware sets CSP, `X-Frame-Options`,
  `nosniff`, `Referrer-Policy`, `Permissions-Policy`. All token access funnelled through
  `session.ts` so the storage mechanism has a single seam to change.
- **Still outstanding**: real `httpOnly` + `Secure` + `SameSite` cookies. That needs
  Sanctum cookie auth, `supports_credentials`, CSRF handling and frontend changes.

### 1.5 CORS methods and headers ✅
- **What**: `allowed_methods` and `allowed_headers` were both `['*']`.
- **How**: Restricted to `GET, POST, PUT, PATCH, DELETE` and
  `Content-Type, Authorization, X-Requested-With`.

---

# 2. Authentication & Authorization Gaps ✅

Eight controllers had missing or inconsistent authorization, plus two correctness issues
in how roles are named.

| # | Item | Status | Notes |
|---|------|--------|-------|
| 2.1 | `CustomerController` — no authorization at all | ✅ | New `CustomerPolicy`; `authorize()` on all five methods. Reads open (till and credit screens need them), writes behind `canManageBranch()` |
| 2.2 | `PlanController` writes ungated | ✅ | New `PlanPolicy`; writes behind `isPlatformOperator()`. `index` stays public for the pricing page |
| 2.3 | `ExpenseController::approve()` ungated | ✅ | New `ExpensePolicy`; `approve()` behind `canManageSensitiveFinance()`, rest behind `canManageBranch()` |
| 2.4 | `SalaryController` — no authorization at all | ✅ | New `SalaryPolicy`; **reads too**, since the list discloses one role's pay against another's |
| 2.5 | `PrinterController` | ✅ | New `PrinterPolicy`. `store`/`update` were already gated; reads were not |
| 2.6 | `PaymentGatewayController::destroy()` ungated | ✅ | `canManagePaymentConfig()` — deleting credentials is worse than writing them |
| 2.7 | `CurrencyRateController::destroy()` ungated | ⚠️ | See deviation below |
| 2.8 | WhatsApp `logs()` / `templates()` ungated | ✅ | `canManagePaymentConfig()`. Securing existing endpoints, not integration work |
| 2.9 | `AiController::tools()` ungated | ✅ | `isElevated()`, matching `chat()` — otherwise the whole tool surface was enumerable by any signed-in user |
| 2.10 | `ReceiptController::pdf()` ungated | ✅ | New `ReceiptPolicy` with a distinct `print()` ability, since it renders PII to a file rather than JSON |
| 2.11 | Raw role-name comparisons | ✅ | `RolePermissions::excludeRoleNames()` plus `COUNTERPARTY_ROLES` / `OWNER_ROLE` constants |
| 2.12 | `sucked` status typo | ✅ | → `blocked` in `StoreUserRequest` **and** `UpdateUserRequest` |

### Deviations

**2.7** — originally specified `isPlatformOperator()`. But `CurrencyRate` is scoped per
business here (`where('business_id', ...)`) and `store`/`update` already sit at
`canManagePaymentConfig()`. `isPlatformOperator()` is siteadmin-only and would have
stopped a tenant that can *write* a rate from *retiring* one. Matched the existing floor.

**2.11** — the four sites could not simply call `RolePermissions` methods, because
`roleName()` normalises in PHP and that is impossible inside a `WHERE` clause: the
stored spelling varies (`Executive`, `editor`, `BranchManager`, `branch_manager`) while
the caller only knows the canonical name. `excludeRoleNames()` normalises the *column*
in SQL instead and compares against normalised literals — same invariant, one place.
`SuperAdminBusinessController` moved to `isPlatformOperator()`, which also fixed a real
bug: its inline `strtolower($user->role?->name)` read a relation the tenant scope blanks
out for CoreSupport, which has no business by design.

---

# 3. Error Handling ✅

| # | Item | Status | Notes |
|---|------|--------|-------|
| 3.1 | Swallowed exceptions in `BusinessService` | ✅ | Split two ways — see below |
| 3.2 | `Exception` carrying HTTP code 301 | ✅ | Typed `InsufficientStockException extends ConflictHttpException` |
| 3.3 | No `ValidationException` renderer | ✅ | `message` names the field; `errors` map unchanged |
| 3.4 | No account lockout | ✅ | `LoginThrottle`, 5 attempts, 429 + `Retry-After`, 6 tests |

### 3.1 — why the two swallows were treated differently
Both sit inside the same transaction, and both were `catch → log → continue`.

- **`attachTrialSubscription()` now rolls back.** It creates the subscription row the
  billing screens, lifecycle job and trial-expiry notification all read. Swallowing a
  failure there committed a tenant that reports as "never subscribed" — onboarding said
  success. A failed insert is a half-built tenant, not a degraded one.
- **`provisionRecipients()` moved to a queued job.** A missing notification-preference
  row must not cost the owner their business, but logging alone means a transient
  failure stays failed until someone reads the log. `ProvisionOwnerRecipientsJob` runs
  `afterCommit()` with retries.

### 3.2 — why 301 was worse than it looks
`throw new Exception(..., 301)` is "Moved Permanently". `PosController` reads
`$e->getCode()` as the HTTP status and clamps anything outside 400–599, so an oversold
cart answered **500**: the till reported a server fault for a shelf conflict and invited
a retry of the identical cart. The new exception renders as 409 and carries
`product_id` / `requested` / `available` / `shortfall`.

### 3.4 — what the lockout does *not* do
No per-IP lockout. That was tried and reverted: several staff behind one NAT share the
counter, so three people mistyping a password apiece locked each other out while the
intended target never had to be attacked at all. The account key is the control;
per-IP attempts are recorded for alerting only, and `throttle:auth` already bounds floods.

Two bugs the new tests caught in this section's own implementation, both worth keeping
in mind for any future limiter work:
- `RateLimiter::availableIn()` returns the timer key's TTL, which exists after the
  *first* hit. Read alone it reported a lockout after one failed attempt. The correct
  API is `tooManyAttempts()` / `remaining()`.
- The account lockout and the route limiter return different 429 messages, so a caller
  can tell which one fired.

---

# 4. Data Integrity & Race Conditions ⬜

Not started.

| # | Item | Status |
|---|------|--------|
| 4.1 | `StockTransferService` dispatch/receive have no `lockForUpdate()` — concurrent transfers lose updates | ⬜ |
| 4.2 | Receipt number claimed before the transaction opens | ⬜ |
| 4.3 | Purchase order number from `count() + 1` | ⬜ |
| 4.4 | Coupon code from `count() + 1` | ⬜ |
| 4.5 | `SaleReturnService` over-return check is unlocked | ⬜ |
| 4.6 | `ExpenseController` update/destroy not transactional | ⬜ |
| 4.7 | `CashFlowController` store/update/destroy not transactional | ⬜ |
| 4.8 | Same in ~8 other controllers | ⬜ |
| 4.9 | `resolveDestinationProduct()` can create duplicate products | ⬜ |
| 4.10 | Cash flow rows are hard-deletable | ⬜ |

**How**: Follow the pattern already in `PosService::checkout()` — `DB::transaction()`
with `lockForUpdate()` on the rows being mutated. For sequential numbers use a Postgres
sequence rather than `count() + 1`. For 4.10, financial records should be immutable with
correcting entries, or at minimum soft-deleted.

---

# 5. Input Validation Gaps ⬜

Not started.

| # | Item | Status |
|---|------|--------|
| 5.1 | POS checkout never checks payments cover the total, or rejects negative amounts — revenue loss | ⬜ |
| 5.2 | Supplier and stock-transfer branch IDs not checked against the caller's business | ⬜ |
| 5.3 | POS search has no minimum query length | ⬜ |
| 5.4 | No duplicate SKU / barcode check per branch | ⬜ |
| 5.5 | Export date range unbounded | ⬜ |
| 5.6 | Upload validates client-reported MIME only, never file content | ⬜ |
| 5.7 | ~11 endpoints validate inline instead of via Form Request | ⬜ |

---

# 6. Performance — N+1 Queries & Unpaginated Endpoints ⬜

Not started. Six N+1 sites (`SaleItemService`, `ProductService`, `ProcurementService`,
`StockTransferService`, `ProductController::restocking`) and fourteen unpaginated list
endpoints, including `ProductController`, `SaleController`, `PurchaseController` and
`UserController`.

**How**: `->paginate()` with a validated `per_page`, following `FinanceController::transactions()`.

---

# 7. Code Quality — Dead Code, Duplication, Large Methods ⬜

Not started. The dead-code items overlap §12, now done.

Still open: `PosService::checkout()` at 200 lines, `SaleReturnService::handleCreateSaleReturn()`
at 90, business logic in `ProductController::restocking()` and `CouponController::store()`,
duplicated `currentUser()` across three middleware, and a duplicated analytics try/catch
pattern across three controllers.

---

# 8. Frontend — State Management, Bundle, Types ⬜

Not started. Largest item is consolidating 51 RTK Query slices into the shared `baseApi`
so tag invalidation actually works across domains. Also: Chart.js not lazy-loaded, a
474 KB PNG logo, TypeScript strictness disabled, ESLint `--max-warnings=931`, `any`
types in four components, no route-level error boundaries, missing loading/empty states
in most dashboard tables, no `aria-live` on the AI chat, no skip-to-content link.

---

# 9. DevOps & Configuration ⬜

Not started. Frontend tests are not run in CI (9.4) is the cheapest item here and worth
taking early while the suite is green. Also: no TLS termination in the production nginx,
no resource limits in compose, no monitoring, no dependency audit in CI, an outdated
database dump, and no staging environment.

---

# 10. Testing Gaps ⬜

Not started. Frontend tests not in CI; flaky time-dependent assertions in
`BranchPerformanceReportsTest`; no integration test proving tenant isolation through the
API; no concurrency tests for POS checkout or stock transfer.

---

# 11. Modules to Add ⬜

Not started: global exception handler (§1.3 delivered this — drop it from this list),
account lockout (§3.4 delivered — drop it), request logging middleware, database
constraints and indexes, soft deletes on financial records, a feature-flag system,
audit trail for financial modifications, and auto-generated OpenAPI docs.

---

# 12. Modules to Remove Before Launch ✅

| # | Item | Status | Notes |
|---|------|--------|-------|
| 12.1 | Empty `SaleService` | ✅ | Deleted — unreferenced |
| 12.2 | Empty `Services/LoginRequest.php` | ✅ | Deleted — 0 bytes, stray duplicate of `Http/Requests/LoginRequest` |
| 12.3 | Empty `CashFlowFactory` definition | ⚠️ | **Implemented, not deleted** |
| 12.4 | `react-icons` | ✅ | Removed; four brand marks inlined |
| 12.5 | `shadcn` in `dependencies` | ✅ | Moved to `devDependencies` |
| 12.6 | Unused `tw-animate-css` | ⚠️ | **Kept** — it is load-bearing |
| 12.7 | Commented-out code blocks | ✅ | Eight files cleaned |
| 12.8 | `ToastComponent.tsx` | ✅ | Deleted — unreferenced demo harness |

### Two findings in this section were wrong

**12.6** — `tw-animate-css` was recorded as imported but unused. It is load-bearing:
`dialog`, `sheet`, `tooltip`, `popover`, `accordion` and `alert-dialog` all use its
`animate-in` / `fade-in-0` / `zoom-in-95` / `slide-in-from-*` utilities. Removing it
would have silently broken every animation in the app, with no type or build error to
catch it.

**12.3** — `CashFlowFactory` was recorded as dead. Three report tests build rows through
it, so deleting it would have broken them. Its `definition()` also returned `[]`, and
every caller passed a complete row inline without supplying `direction` or `currency` —
the fields the reporting queries aggregate on. A `CashFlow` with a null direction is a
row the reports cannot sum, so the empty definition hid a real invariant behind a factory
that appeared to work.

---

## Summary priority matrix

| Priority | Category | Count | Action |
|----------|----------|-------|--------|
| P0 | Secrets in repo | 3 files | ✅ Rotations still manual |
| P0 | Stack trace leakage | 49 sites | ✅ |
| P0 | Missing authorization | 8 controllers | ✅ |
| P1 | Race conditions | 5 sites | ⬜ |
| P1 | Missing transactions | ~10 sites | ⬜ |
| P1 | N+1 queries | 6 sites | ⬜ |
| P1 | Unpaginated endpoints | 14 | ⬜ |
| P1/P2 | Frontend state + bundle | 51 slices | ⬜ |
| P2 | Validation gaps | 7 sites | ⬜ |
| P2 | Code quality | 8 sites | ⬜ |
| P2 | DevOps | 5 items | ⬜ |
| P2 | Testing gaps | 4 items | ⬜ |
| P3 | Accessibility | 3 items | ⬜ |
| P3 | Type safety | 5 sites | ⬜ |

---

*End of findings.*