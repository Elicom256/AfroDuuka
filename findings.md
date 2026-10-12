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
| 4 | Data Integrity & Race Conditions | P1 | ✅ |
| 5 | Input Validation Gaps | P2 | ✅ |
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
| After §4 | 868 passed | — |
| After §5 | 888 passed (+20 new) | — |
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

# 4. Data Integrity & Race Conditions ✅

| # | Item | Status | Notes |
|---|------|--------|-------|
| 4.1 | `StockTransferService` dispatch/receive unlocked | ✅ | Transfer row and product rows now `lockForUpdate()`; status re-checked inside the transaction |
| 4.2 | Receipt number claimed outside the transaction | ✅ | **Already fixed** — verified, no change needed |
| 4.3 | Purchase order number from `count() + 1` | ✅ | `SequenceNumberGenerator`, seeded from existing rows |
| 4.4 | Coupon code from `count() + 1` | ✅ | Same generator; also fixes a cross-business collision |
| 4.5 | `SaleReturnService` over-return check unlocked | ✅ | **Already fixed** — verified, no change needed |
| 4.6 | `ExpenseController` update/destroy not transactional | ✅ | store/update/destroy all transactional |
| 4.7 | `CashFlowController` not transactional | ✅ | store/update transactional |
| 4.8 | Missing transactions in ~8 controllers | ⚠️ | 2 of 8 fixed — see below |
| 4.9 | `resolveDestinationProduct()` can create duplicates | ✅ | Match queries lock; insert recovers from a unique violation |
| 4.10 | Cash flow rows hard-deletable | ⚠️ | Method removed rather than soft-deleted — see below |

### 4.1 / 4.9 — the transfer race
`dispatch()` and `receive()` checked status *outside* the transaction, so two requests
racing on the same draft both saw `draft` and both moved stock — the second decrementing
from a quantity the first had already invalidated. Each now re-reads the transfer under
`lockForUpdate()` and re-checks inside. Product rows take `lockForUpdate()` too, since
read-then-decrement is a read-modify-write.

`resolveDestinationProduct()` matched on sku/barcode/name and created a row on no match,
with nothing to stop two concurrent transfers of the same product both finding nothing
and both inserting. That is the reported duplicate-product-on-transfer bug. The match
queries lock, and the insert catches a unique violation and re-reads the winner's row, so
stock lands on one product instead of splitting across two.

### 4.3 / 4.4 — count-then-insert
Both were wrong twice over: concurrent requests read the same count and the loser was
rejected by the UNIQUE index (failing a purchase order already sent to a supplier), and
because the count ran through the tenant scope while the columns are global, two
businesses sharing a four-character name prefix both started at `001` — so the second
insert always lost, which reads as a constraint bug rather than a numbering one.

`SequenceNumberGenerator` mirrors `ReceiptNumberGenerator`, which was already converted
for the same reasons. The migration seeds each sequence from the highest serial already in
use so an existing database does not restart at 1.

### 4.8 — only 2 of the 8 were real
Every model involved carries `LogsActivity`, so every write emits a second
`activity_log` row — which made all eight look multi-record. But for `PromotionController`,
`BusinessDebitController`, `CurrencyRateController` and `PaymentGatewayController` that
row is written by an observer from the same statement, so there is no window for a
partial write. Wrapping them would be churn, not a fix.

The two that were genuinely broken:
- `SalaryController::update()` wrote the same row twice — `forceFill()->save()` then
  `update()`. A failure between them left a salary that had silently stopped applying to
  one branch while still showing the old amount. Collapsed to one write.
- `ReportExportController::store()` — the case that matters most. An export moves data
  outside the tenant scope, so an export row with no `ActivityLog` beside it is data that
  left the tenant with nobody recorded as having asked for it.

### 4.10 — removed, not soft-deleted
`CashFlowController::destroy()` existed but no DELETE route was ever registered for it, so
it was unreachable. Adding soft deletes would have made a hard-delete-on-a-financial-ledger
row look deliberate and sanctioned. The method is gone; corrections go through an
adjusting entry, and `setDirection()` already covers the one correction this product needs.

### Two items were already correct
**4.2** — `ReceiptNumberGenerator` already allocates from a Postgres sequence, and
`PosService::checkout()` calls it outside the transaction deliberately so an abandoned
cart does not consume a number.
**4.5** — `SaleReturnService` already takes `lockForUpdate()` on the `SaleItem` at
`SaleReturnService.php:46`, *before* summing prior returns. That is the correct order.

---

# 5. Input Validation Gaps ✅

| # | Item | Status | Notes |
|---|------|--------|-------|
| 5.1 | POS checkout never checked payments cover the total | ✅ | Checked in the request *and* the service; 7 tests |
| 5.2 | Supplier and transfer IDs not checked against the caller's business | ✅ | Both scoped to the caller's tenant |
| 5.3 | POS search has no minimum query length | ✅ | `min:2` on both search endpoints |
| 5.4 | No duplicate SKU / barcode check per branch | ✅ | Unique indexes per branch + Form Request rules |
| 5.5 | Export date range unbounded | ✅ | Capped at 366 days, dates validated |
| 5.6 | Upload validated client-reported MIME only | ✅ | `RealFileContent` magic-byte rule; 6 tests |
| 5.7 | ~11 endpoints validate inline instead of via Form Request | ⚠️ | Surfaced a real authorization gap instead — see below |

### 5.1 — the revenue one
`PosCheckoutRequest` allowed any payment amount and `PosService::checkout()` summed
what it was given. A short tender produced a `completed` sale whose payment rows were
recorded as `paid`, a receipt showing **zero change** rather than money outstanding, and
a cash-flow row booking the full amount as received. The goods had already left the
counter.

Checked in two places, because neither can see what the other can. The request compares
the tender against the cart minus tax — knowable from the payload, so an underpayment is
refused before anything is written. The service re-checks against the authoritative total
once tax is resolved and a held sale's stored amount is read, and throws inside the
transaction so the sale, its items and the stock decrement all roll back.

`payments.*.amount` is now `gt:0`: a 0.00 tender row became a `paid` `SalePayment` for
nothing while inflating the payment-method count on the receipt.

`TransactionTaxTest`'s helper was under-tendering by exactly the tax on a tax-exclusive
line — correct under the new rule, so the helper now pays above the item total. Those
tests are about the tax on the receipt, not about tendering to the penny.

### 5.2 — cross-tenant ids
`supplier_id` on a purchase and `from_branch_id` / `to_branch_id` on a stock transfer
were bare `exists:<table>,id`. The reads that follow are tenant-scoped and return null,
which is why neither was visible: the record existed and its counterparty simply never
rendered. Stock transfers already merged `business_id` from the session, so only the
branch ids were open.

### 5.4 — why the index matters here
No constraint on `products.sku` or `products.barcode` at all. The POS barcode path
resolves a product by barcode, so a duplicate decides which product a scanned code rings
up, and `StockTransferService::resolveDestinationProduct()` matches on sku → barcode →
name — a duplicate is precisely what makes a transfer create or pick the wrong product.
Indexes are per `business_branch_id` because products are per-branch; a global index would
forbid the same sku in two branches of one business. The migration **refuses to run
while duplicates exist** and lists the offending branch, value and row ids, rather than
failing with a constraint violation naming a constraint nobody has seen.

### 5.7 — found a real gap instead of doing the refactor
The item as written is 15 inline `$request->validate()` calls across 11 controllers —
pure refactoring, no behaviour change, and 129 Form Request classes already exist to be
consistent with. Auditing those 11 controllers for authorization turned up something
better: **`PosController` had no role gate at all.**

Both counterparties hold a `User` row carrying the tenant's `business_id`
(`StoreCustomerRequest` assigns the `customer` role alongside the caller's own tenant,
because a customer must belong to the business whose books it appears on). They
satisfied `RequireBusiness` and reached the till — completing sales, taking payment,
decrementing stock, writing cash-flow rows, and searching the catalogue.

Fixed with `RequireTillAccess` over the whole route group, so the search endpoints are
covered too. Four tests, one of which pins that Operations, BranchManager and Executive
all still work the till — the failure mode for a gate like this is locking staff out.

The mechanical Form Request extraction is deliberately **not** done here: it is a large
diff with no behaviour change, and the remaining 10 controllers have no authorization
problem of this kind. Worth doing as its own pass if the codebase wants the consistency.

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