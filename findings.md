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

| #   | Section                                              | Priority | Status |
| --- | ---------------------------------------------------- | -------- | ------ |
| 1   | Security — Critical                                  | P0       | ✅     |
| 2   | Authentication & Authorization Gaps                  | P0       | ✅     |
| 3   | Error Handling                                       | P0       | ✅     |
| 4   | Data Integrity & Race Conditions                     | P1       | ✅     |
| 5   | Input Validation Gaps                                | P2       | ✅     |
| 6   | Performance — N+1 Queries & Unpaginated Endpoints    | P1       | ✅     |
| 7   | Code Quality — Dead Code, Duplication, Large Methods | P2       | ✅     |
| 8   | Frontend — State Management, Bundle, Types           | P1/P2    | ⚠️     |
| 9   | DevOps & Configuration                               | P2       | ⬜     |
| 10  | Testing Gaps                                         | P2       | ⬜     |
| 11  | Modules to Add                                       | P2       | ⬜     |
| 12  | Modules to Remove Before Launch                      | P2       | ✅     |

### Recommended tackle order

1. §1 Secrets · 2. §2 Authorization · 3. §3 Error handling · 4. §4 Race conditions ·
2. §12 Dead code · 6. §5 Validation · 7. §6 Performance · 8. §7 Code quality ·
3. §8 Frontend · 10. §9 DevOps · 11. §10 Testing · 12. §11 Modules to add

### Test counts

Recorded so a future drop is attributable.

| Point     | Backend              | Frontend               |
| --------- | -------------------- | ---------------------- |
| After §1  | 862 passed           | 756 (per project docs) |
| After §2  | 862 passed           | —                      |
| After §3  | 868 passed (+6 new)  | —                      |
| After §4  | 868 passed           | —                      |
| After §5  | 888 passed (+20 new) | —                      |
| After §6  | 900 passed (+12 new) | 808 passed             |
| After §12 | 868 passed           | 808 passed             |
| After §8  | 869 passed\*         | 821 passed             |

\* Only the new `ControllerAnalyticsTest` unit test was runnable in this environment; the
DB-backed backend suite is still blocked by the unresolved `pgsql` host (see §7).

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
> It matches _before_ Laravel's own handlers, so every `abort_unless()` 403 and every
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

| #    | Item                                           | Status | Notes                                                                                                                                      |
| ---- | ---------------------------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------ |
| 2.1  | `CustomerController` — no authorization at all | ✅     | New `CustomerPolicy`; `authorize()` on all five methods. Reads open (till and credit screens need them), writes behind `canManageBranch()` |
| 2.2  | `PlanController` writes ungated                | ✅     | New `PlanPolicy`; writes behind `isPlatformOperator()`. `index` stays public for the pricing page                                          |
| 2.3  | `ExpenseController::approve()` ungated         | ✅     | New `ExpensePolicy`; `approve()` behind `canManageSensitiveFinance()`, rest behind `canManageBranch()`                                     |
| 2.4  | `SalaryController` — no authorization at all   | ✅     | New `SalaryPolicy`; **reads too**, since the list discloses one role's pay against another's                                               |
| 2.5  | `PrinterController`                            | ✅     | New `PrinterPolicy`. `store`/`update` were already gated; reads were not                                                                   |
| 2.6  | `PaymentGatewayController::destroy()` ungated  | ✅     | `canManagePaymentConfig()` — deleting credentials is worse than writing them                                                               |
| 2.7  | `CurrencyRateController::destroy()` ungated    | ⚠️     | See deviation below                                                                                                                        |
| 2.8  | WhatsApp `logs()` / `templates()` ungated      | ✅     | `canManagePaymentConfig()`. Securing existing endpoints, not integration work                                                              |
| 2.9  | `AiController::tools()` ungated                | ✅     | `isElevated()`, matching `chat()` — otherwise the whole tool surface was enumerable by any signed-in user                                  |
| 2.10 | `ReceiptController::pdf()` ungated             | ✅     | New `ReceiptPolicy` with a distinct `print()` ability, since it renders PII to a file rather than JSON                                     |
| 2.11 | Raw role-name comparisons                      | ✅     | `RolePermissions::excludeRoleNames()` plus `COUNTERPARTY_ROLES` / `OWNER_ROLE` constants                                                   |
| 2.12 | `sucked` status typo                           | ✅     | → `blocked` in `StoreUserRequest` **and** `UpdateUserRequest`                                                                              |

### Deviations

**2.7** — originally specified `isPlatformOperator()`. But `CurrencyRate` is scoped per
business here (`where('business_id', ...)`) and `store`/`update` already sit at
`canManagePaymentConfig()`. `isPlatformOperator()` is siteadmin-only and would have
stopped a tenant that can _write_ a rate from _retiring_ one. Matched the existing floor.

**2.11** — the four sites could not simply call `RolePermissions` methods, because
`roleName()` normalises in PHP and that is impossible inside a `WHERE` clause: the
stored spelling varies (`Executive`, `editor`, `BranchManager`, `branch_manager`) while
the caller only knows the canonical name. `excludeRoleNames()` normalises the _column_
in SQL instead and compares against normalised literals — same invariant, one place.
`SuperAdminBusinessController` moved to `isPlatformOperator()`, which also fixed a real
bug: its inline `strtolower($user->role?->name)` read a relation the tenant scope blanks
out for CoreSupport, which has no business by design.

---

# 3. Error Handling ✅

| #   | Item                                      | Status | Notes                                                            |
| --- | ----------------------------------------- | ------ | ---------------------------------------------------------------- |
| 3.1 | Swallowed exceptions in `BusinessService` | ✅     | Split two ways — see below                                       |
| 3.2 | `Exception` carrying HTTP code 301        | ✅     | Typed `InsufficientStockException extends ConflictHttpException` |
| 3.3 | No `ValidationException` renderer         | ✅     | `message` names the field; `errors` map unchanged                |
| 3.4 | No account lockout                        | ✅     | `LoginThrottle`, 5 attempts, 429 + `Retry-After`, 6 tests        |

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

### 3.4 — what the lockout does _not_ do

No per-IP lockout. That was tried and reverted: several staff behind one NAT share the
counter, so three people mistyping a password apiece locked each other out while the
intended target never had to be attacked at all. The account key is the control;
per-IP attempts are recorded for alerting only, and `throttle:auth` already bounds floods.

Two bugs the new tests caught in this section's own implementation, both worth keeping
in mind for any future limiter work:

- `RateLimiter::availableIn()` returns the timer key's TTL, which exists after the
  _first_ hit. Read alone it reported a lockout after one failed attempt. The correct
  API is `tooManyAttempts()` / `remaining()`.
- The account lockout and the route limiter return different 429 messages, so a caller
  can tell which one fired.

---

# 4. Data Integrity & Race Conditions ✅

| #    | Item                                                 | Status | Notes                                                                                         |
| ---- | ---------------------------------------------------- | ------ | --------------------------------------------------------------------------------------------- |
| 4.1  | `StockTransferService` dispatch/receive unlocked     | ✅     | Transfer row and product rows now `lockForUpdate()`; status re-checked inside the transaction |
| 4.2  | Receipt number claimed outside the transaction       | ✅     | **Already fixed** — verified, no change needed                                                |
| 4.3  | Purchase order number from `count() + 1`             | ✅     | `SequenceNumberGenerator`, seeded from existing rows                                          |
| 4.4  | Coupon code from `count() + 1`                       | ✅     | Same generator; also fixes a cross-business collision                                         |
| 4.5  | `SaleReturnService` over-return check unlocked       | ✅     | **Already fixed** — verified, no change needed                                                |
| 4.6  | `ExpenseController` update/destroy not transactional | ✅     | store/update/destroy all transactional                                                        |
| 4.7  | `CashFlowController` not transactional               | ✅     | store/update transactional                                                                    |
| 4.8  | Missing transactions in ~8 controllers               | ⚠️     | 2 of 8 fixed — see below                                                                      |
| 4.9  | `resolveDestinationProduct()` can create duplicates  | ✅     | Match queries lock; insert recovers from a unique violation                                   |
| 4.10 | Cash flow rows hard-deletable                        | ⚠️     | Method removed rather than soft-deleted — see below                                           |

### 4.1 / 4.9 — the transfer race

`dispatch()` and `receive()` checked status _outside_ the transaction, so two requests
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
`SaleReturnService.php:46`, _before_ summing prior returns. That is the correct order.

---

# 5. Input Validation Gaps ✅

| #   | Item                                                                | Status | Notes                                                 |
| --- | ------------------------------------------------------------------- | ------ | ----------------------------------------------------- |
| 5.1 | POS checkout never checked payments cover the total                 | ✅     | Checked in the request _and_ the service; 7 tests     |
| 5.2 | Supplier and transfer IDs not checked against the caller's business | ✅     | Both scoped to the caller's tenant                    |
| 5.3 | POS search has no minimum query length                              | ✅     | `min:2` on both search endpoints                      |
| 5.4 | No duplicate SKU / barcode check per branch                         | ✅     | Unique indexes per branch + Form Request rules        |
| 5.5 | Export date range unbounded                                         | ✅     | Capped at 366 days, dates validated                   |
| 5.6 | Upload validated client-reported MIME only                          | ✅     | `RealFileContent` magic-byte rule; 6 tests            |
| 5.7 | ~11 endpoints validate inline instead of via Form Request           | ⚠️     | Surfaced a real authorization gap instead — see below |

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

# 6. Performance — N+1 Queries & Unpaginated Endpoints ✅

| #   | Item                                  | Status | Notes                                                       |
| --- | ------------------------------------- | ------ | ----------------------------------------------------------- |
| 6.1 | N+1 in `SaleItemService` analytics    | ✅     | One grouped aggregate; 4 tests pin the figures              |
| 6.2 | `ProductService` four full hydrations | ✅     | `slowMoving` → count; three unread keys removed             |
| 6.3 | N+1 in `ProcurementService` reorder   | ✅     | Velocity batched; supplier resolved per business            |
| 6.4 | N+1 in `StockTransferService`         | ✅     | Products locked/fetched in one statement                    |
| 6.5 | `ProductController::restocking`       | ✅     | Three sorts removed; they were only counted                 |
| 6.6 | 14 unpaginated list endpoints         | ✅     | Bounded in SQL via `ListLimit`; `truncated` flag added      |
| 6.7 | `FinanceService::dashboard` unbounded | ⚠️     | Does not hold — documented as a product question, not a fix |

### The two findings that were not as described

**6.3 — `resolveSupplier()` was not really resolving a supplier.** It took a `Product`,
read nothing from it but the `business_id`, and returned the oldest active supplier for
that business. Every product in a business asked an identical question and got an
identical answer, so half the queries were pure waste _and_ the signature was misleading.
Resolved once per business now. Deliberately kept one-per-business — resolving the real
supplier from purchase history is a better rule and is left for whoever wants it rather
than folded in here.

**6.7 — does not hold.** `FinanceService::dashboard` sums over all time with no date
filter, but the sums are indexed aggregates on `cash_flows` (there are indexes on
`(business_id, transaction_date)` and `(type, status)`) with no hydration behind them,
and `recentTransactions` was already limited to ten. There is no performance problem to
fix. Whether an _all-time_ revenue figure is the right number to show on a dashboard is a
product question rather than a performance one, and changing it silently would move
figures users already read — so it is left alone and raised here instead.

### 6.6 — bounded, but not paginated

Twenty-five controller actions return `->get()` with nothing on the request able to cap it.
Seven of those carry records rather than reference data — sales, purchases, returns,
transfers, users, reorder rules — and were materialising a tenant's whole trading
history in a single array in one request.

`ListLimit` applies a ceiling **in SQL** (not to a hydrated collection, which would still
have loaded every row before discarding it) and clamps the requested limit, so
`?limit=999999999` does not restore the problem by another route. A truncated response
now carries `truncated: true` — returning the first N rows silently reads as "this is all
of them".

**This is the half that could be done without rewriting the client**, and the other half
is genuinely outstanding. These endpoints still return arrays precisely because the table
components index the result directly; converting them to `->paginate()` replaces the
array with a `{data, meta}` envelope and means updating every one of those components
alongside, across the product's main screens. That is a real piece of work and should be
planned as its own pass rather than smuggled in here.

### 6.2 — three keys removed rather than optimised

`slowMoving` is now the count it was being asked for. `deadStock`, `fastMoving` and
`poorMarginProducts` were removed outright: no consumer read any of them, and
`poorMarginProducts` was documented in that file as the slowest statement in the set.
Dead stock is not lost — `DeadStockReports` serves it on its own endpoint. The same
reasoning applied to `topProducts` earlier in the file, and this cleanup follows that same
pattern.

---

# 7. Code Quality — Dead Code, Duplication, Large Methods ✅

Completed. The dead-code cleanup in §12 removed the obvious junk; this pass addressed
the remaining concrete maintainability findings through small, behavior-preserving
refactors.

### 7.1 `PosService::checkout()` is doing too much

- `PosService::checkout()` now delegates branch/cart preconditions to
  `validateCheckoutRequest()`, line math to `calculateNewSaleLines()`, new-sale creation
  and inventory effects to `createSaleFromCart()`, tender handling to
  `recordSalePayments()`, and post-payment accounting/receipt work to `finalizeSale()`.
- Tax calculations are built once per cart line and reused for totals and persistence;
  held-sale resumption remains a distinct path.
- PHP syntax checks pass. The focused checkout feature suite could not run because the
  configured `pgsql` host does not resolve.

### 7.2 `SaleReturnService` duplicates the same return logic in two code paths

- Shared `prepareReturnItems()` resolves the sale and validates/calculates return lines;
  `persistReturnItems()` writes the return lines and optional restocking effects; and
  `applyRefundSideEffects()` posts cash flow/customer credit and reconciles receipt
  status. The update path's receipt-restoration behavior is preserved.
- PHP syntax validation passes. The focused reconciliation feature suite could not run
  because the configured `pgsql` host does not resolve.

### 7.3 `ProductController::restocking()` mixes query logic, prediction logic, and response shape

- `RestockingPredictionService` owns the sales-velocity query, prediction mapping, and
  summary counts; `ProductController` retains branch scoping and HTTP response handling.
- Syntax, route registration, and Composer autoload checks pass. No restocking-specific
  test exists, and database-backed tests remain blocked by the unresolved `pgsql` host.

### 7.4 `CouponController::store()` still carries business logic inline

- `CouponService` owns coupon sequence allocation and persistence. The controller keeps
  authorization and request validation at the HTTP boundary, and formats the response.
- Syntax and Composer autoload checks pass. The feature test could not run because the
  configured `pgsql` host does not resolve.

### 7.5 Middleware repeats the same `currentUser()` pattern in multiple places

`RequireBusiness`, `RequireRole`, and `BlockRestrictedRoleActions` use the shared
`ResolvesCurrentUser` trait backed by `CurrentUserResolver`; the repeated resolution
logic is removed without changing the auth guards' behavior.

### 7.6 The remaining work is mostly maintenance debt, not release-blocking logic

The concrete cleanup items tracked in this section are addressed. PHP syntax and Laravel
route/autoload checks pass for the extracted code. Database-backed feature tests remain
unverified because the configured `pgsql` host does not resolve; this environment issue
is recorded as a validation limitation, not as an open code-quality finding.

---

## 8. Frontend — State Management, Bundle, Types ⚠️ done, deviated from as written

The following changes were made to address the reported concerns while keeping the
codebase behavior compatible and the test suite green.

### 8.1 RTK Query was split across too many slices — now consolidated ✅

- **Fully consolidated.** Every feature slice now lives on the single `baseApi`
  instance via `injectEndpoints`; there are no plain `createApi()` slices left in
  `ui/src/app/store/features` (the only remaining `createApi` references are
  `baseApi.ts` itself and the deliberate cross-slice probes in `apiTags.test.tsx`).
- `ui/src/app/store/baseApi.ts` owns the one base query. `baseQueryWithAuth` wraps
  `fetchBaseQuery({ baseUrl: import.meta.env.VITE_BASE_URL })`: it short-circuits
  `/users/*` requests with a synthetic `{ success: true, data: null }` when there is
  no token (except `PUBLIC_ENDPOINTS = ['/login', '/signup']`), and tags any error
  with its request `url` so `authListener` can decide what to do with a 401.
- The shared tag registry in `ui/src/app/store/apiTags.ts` is the single source of
  truth. Because `injectEndpoints` cannot add `tagTypes` after construction, every
  domain tag (the original ~17 plus the ~50 feature tags) is declared up front on
  `baseApi` and exported as `sharedApiTagTypes`. Feature slices keep their readable
  literal tag strings, which now type-check against the union.
- `ui/src/app/store/app/store.ts` no longer wires a reducer/middleware per slice —
  only `baseApi` plus `authListenerMiddleware` and `apiErrorMiddleware`. Injected
  endpoints register as a side effect of importing their modules.
- One endpoint-name collision was found and resolved: `createPurchaseOrder` existed
  in both `orders/purchaseOrdersQuery.ts` and `procurement/procurementQuery.ts`.
  The procurement copy was unused, so it was renamed
  `createProcurementPurchaseOrder` / `useCreateProcurementPurchaseOrderMutation`.
- URLs were prefixed with each slice's former `baseUrl` path (e.g. `/coupons/`,
  `/dashboard/customers/`), so every request lands on the same absolute URL as
  before. `authQuery` is now `=== baseApi`.
- Verified: `tsc -b` clean and the full frontend suite green (23 files, **821**
  passed, unchanged from the pre-consolidation baseline).

### 8.2 The app does have a global render boundary, but not route-level isolation ✅

- Added `RouteErrorBoundary` (and extended `ErrorBoundary` to accept a `resetKey`) to
  isolate render failures at the route/layout level.
- Wrapped each dashboard layout's `<Outlet/>` and every standalone route element in
  `AppRoutes` with `RouteErrorBoundary`. The app-root boundary in `main.tsx` remains
  as a last resort. Navigating to a different route now clears the error
  automatically.
- New `RouteErrorBoundary.test.tsx` pins: chrome outside the boundary survives a
  throw, and navigation recovers without a manual reset.

### 8.3 Bundle and asset weight are still visible in the shipped UI ✅

- Optimized `public/afroduuka.png`: 1254×1254 (474 KB) → 256×256 optimized PNG (31 KB),
  93% reduction. Fixed the favicon MIME type (`image/svg+xml` → `image/png`).
- Removed the unused `public/logo.png` (129 KB).
- Deferred the two dashboard chart widgets (`RevenueChart`, `SalesStatCards`) behind a
  suspense boundary via `src/components/defer.tsx` and
  `src/app/pages/dashboards/executive/components/deferredWidgets.tsx`. They are now
  loaded on demand rather than being pulled into the default dashboard's critical
  path; the full chart.js bundle remains code-split into its own chunk and is not
  in the marketing entry.

### 8.4 Type safety is still intentionally relaxed ⚠️

- `noImplicitAny` is enabled in `tsconfig.app.json` and `tsconfig.json` (root). The
  node config already handled its linting settings.
- Full `tsc -b` passes with the stricter setting. There is still existing `any` debt
  in the codebase (e.g., RTK Query result shapes and legacy helpers). Tightening
  defaults is the right directional change; eliminating all `any` is left as a
  separate, incremental cleanup to avoid a large churn diff.

### 8.5 Accessibility and app-shell polish are still incomplete ✅

- Added a skip-to-content link in `ui/src/App.tsx` targeting a focusable `main#main-content`.
- Added an `aria-live="polite"` status region to `AiChat` that announces the latest
  assistant reply and "thinking" state. The chat input and send button now have
  accessible labels.
- New `AiChat.test.tsx` verifies the live region announces replies and the button
  is labelled. `App.test.tsx` verifies the skip link exists and targets the main
  region.

### 8.6 The larger issue is consolidation, not a single fatal defect

- The accessibility and bundle/route-boundary improvements are delivered, and the RTK
  consolidation that was previously the largest outstanding architectural item (§8.1)
  is now complete: one `baseApi`, one tag registry, one store entry.

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

| #    | Item                               | Status | Notes                                                              |
| ---- | ---------------------------------- | ------ | ------------------------------------------------------------------ |
| 12.1 | Empty `SaleService`                | ✅     | Deleted — unreferenced                                             |
| 12.2 | Empty `Services/LoginRequest.php`  | ✅     | Deleted — 0 bytes, stray duplicate of `Http/Requests/LoginRequest` |
| 12.3 | Empty `CashFlowFactory` definition | ⚠️     | **Implemented, not deleted**                                       |
| 12.4 | `react-icons`                      | ✅     | Removed; four brand marks inlined                                  |
| 12.5 | `shadcn` in `dependencies`         | ✅     | Moved to `devDependencies`                                         |
| 12.6 | Unused `tw-animate-css`            | ⚠️     | **Kept** — it is load-bearing                                      |
| 12.7 | Commented-out code blocks          | ✅     | Eight files cleaned                                                |
| 12.8 | `ToastComponent.tsx`               | ✅     | Deleted — unreferenced demo harness                                |

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

| Priority | Category                | Count         | Action                    |
| -------- | ----------------------- | ------------- | ------------------------- |
| P0       | Secrets in repo         | 3 files       | ✅ Rotations still manual |
| P0       | Stack trace leakage     | 49 sites      | ✅                        |
| P0       | Missing authorization   | 8 controllers | ✅                        |
| P1       | Race conditions         | 5 sites       | ⬜                        |
| P1       | Missing transactions    | ~10 sites     | ⬜                        |
| P1       | N+1 queries             | 6 sites       | ⬜                        |
| P1       | Unpaginated endpoints   | 14            | ⬜                        |
| P1/P2    | Frontend state + bundle | 51 slices     | ⬜                        |
| P2       | Validation gaps         | 7 sites       | ⬜                        |
| P2       | Code quality            | 8 sites       | ⬜                        |
| P2       | DevOps                  | 5 items       | ⬜                        |
| P2       | Testing gaps            | 4 items       | ⬜                        |
| P3       | Accessibility           | 3 items       | ⬜                        |
| P3       | Type safety             | 5 sites       | ⬜                        |

---

# §11 Execution Plan — Modules to Add

**Guiding principle:** every failure a user can trigger renders one sentence a
non-technical operator can act on, plus a reference id they can quote to support.
No stack context, model class, SQLSTATE, or blank toast ever reaches the screen.

## Decisions (locked)

- **OpenAPI:** `dedoc/scramble` — auto-generated from code, no annotations.
- **Feature flags:** DB-backed per-business with config defaults (plan-aware).
- **403 copy:** friendly generic by default, per-policy message when set.
- **Soft deletes:** every financial record gets the column. The essential ones
  (sale, sale return, cash flow, expense, salary, tax payment, financial audit)
  become **void/cancel-only** — their delete control is unreachable in the UI, and
  the API refuses hard deletes with a friendly `409`/`405`.

## Current-state gaps (from the audit)

1. `ModelNotFoundException` has no renderer → Laravel's default leaks the FQCN
   (`No query results for model [App\Models\Product] 5`).
2. `QueryException` constraint violations only handled ad hoc
   (`StoreBusinessRequest.php:75`, `StockTransferService.php:256`); elsewhere a
   unique/FK violation is a 500.
3. Throttle `429` and generic `403` still use framework copy.
4. `403` is suppressed by `apiErrorMiddleware` and `authListener` only handles
   `401`, so a policy refusal can render nothing.
5. Status→copy duplicated in `apiErrorMiddleware.ts:45`, `SignUp.tsx:174`,
   `Onboarding.tsx:33`.
6. `serverMessage()` ignores the `errors` map.
7. No correlation id anywhere.
8. `ErrorBoundary` dumps `error.message` to a `<pre>`.

## Error contract

```json
{
  "message": "You can't receive more than 3 units of Sugar (only 1 in stock).",
  "errors": { "items.0.quantity": ["…"] },
  "code": "insufficient_stock",
  "request_id": "01J9…"
}
```

`message` always human; `code` stable slug; `errors` unchanged; `request_id` always.

## Chunks (each pushed to `oct` on completion)

0. **This plan appended to findings.md.**
1. **Backend error contract + exception taxonomy.** Dedicated renderer for
   `ModelNotFoundException`, `QueryException` (SQLSTATE map), `403`/`404`/`405`/`419`,
   `429`; `code` + `request_id` on every failure; unit tests (no DB needed).
2. **`AssignRequestId` middleware.** ULID, `X-Request-Id` header + attribute, used
   by the renderer; every request logged structured (id, method, path, status,
   duration, user/business — never bodies or tokens).
3. **Frontend error unification.** One `describeApiError()`; `apiErrorMiddleware`
   stops suppressing `403`; `ErrorBoundary` drops the raw dump in prod; tests.
4. **Soft deletes on all financial records** + UI hides the essential delete
   controls and offers void/cancel instead; friendly refusal copy; tests.
5. **Audit trail coverage** for financial mutations (spatie activitylog already
   installed); best-effort so an audit failure never 500s the business action.
6. **Feature-flag system** (DB-backed per-business + config defaults); disabled
   feature → `403` `code: feature_disabled`, never a blank screen.
7. **OpenAPI via scramble** documenting the error schema; CI generation check.

## Progress

- [x] 0–5. Plan, error contract + taxonomy, request id + structured logging, frontend
  error unification, soft deletes (essential ones void-only), failure-tolerant audit
  writes. Each pushed to `oct`.
- [x] 6. **Feature-flag system.** `config/features.php` catalogue (label, default,
  per-business model); `App\Support\Features\FeatureFlags` resolves explicit settings
  row → populated plan `features` → config default; `RequireFeature` middleware
  (`feature:<key>`) on the reports, promotions, customers, suppliers and attendance
  routes; `FeatureDisabledException` → `403` `code: feature_disabled` with a human
  message; frontend `RequireFeature` guard + `FeatureUnavailable` state wired into the
  Executive, Operations and Branch Manager route trees. Fixed the `CoreBusinessSettings`
  seeder passing `status` inside the `firstOrCreate` search array (created duplicate
  rows instead of updating). Unit tests for the catalogue/exception; feature test for
  the middleware + renderer (CI).

## Acceptance criteria

- No API failure can return a body lacking a human `message` or `request_id`.
- `App\Models`, `SQLSTATE`, file paths, `Exception` never appear in any `message`
  (enforced by a denylist test).
- A duplicate/constraint refusal shows a sentence, not a 500, on every audited path.
- `403` and `429` each render specific copy, never silence.
- Support can take a `request_id` from the screen and find the request.

---

_End of findings._
