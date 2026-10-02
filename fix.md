# Fix bugs found

Baseline: `php artisan test` in docker = **534 passed, 0 failed** (was 8 failed, 527 passed).
None of the original 8 were caused by the supplier/product-category work; all predate it.
Each entry below gives the proven root cause, a verdict on the proposal in this file, and
the recommended fix.

| # | Failing test(s) | Root cause | Status |
|---|---|---|---|
| 1 | `AttachmentTest > customer can hold documents` | stale `/api/admin/*` prefix | **fixed** |
| 2 | `OnboardingFlowTest` ×3 (username prefix, duplicate handle, sign-in) | `UserService` double-prefixes `@`; `uniqueUsername()` never implemented; login lookup misses bare handles | **fixed** |
| 3 | `PosCheckoutTest > checkout rejects a held sale owned by another user` | `PosController` maps exception code `0` → 500 | **fixed** |
| 4 | `PosCheckoutTest > credit sale is added to customer ledger…` | test fixture omits `business_id` | **fixed** |
| 5 | `ProbeHeldTest > probe` | scratch test; `Sale::create` drops `business_id` | **fixed** (deleted) |
| 6 | `SignupTest > business creation rejects a missing country` | persistent test DB + absolute `assertSame(0, …)` | **fixed** |

---

## Routes

**Proposal:** *In API files/folders, prefix all routes to the relevant used role. Eg for
executive, it should be `api/executive/…`, for branch manager `/api/branchmanager/…` and so on.*

**Verdict: don't — and the failure we are fixing is the proof.** Commit `76dc0c4` ("UI
refactor") already performed exactly this migration:

```
-Route::prefix("admin")->group(function () {
+Route::prefix("dashboard")->group(function () {
```

It renamed the prefix and never updated the callers. That single line is the entire cause of
error 1 and of 7 broken features. A third prefix move (`/admin` → `/dashboard` → `/executive`
+ `/branchmanager`) reproduces the same failure mode with more files to update.

Three further reasons it is the wrong shape here:

1. **Shared pages make it worse, not better.** `ExecutiveSuppliersPage` and
   `ExecutiveProductsPage` render for *both* roles (`BranchManagerRoutes.tsx` and
   `ExecutiveRoutes.tsx` both mount them). With role-prefixed URLs the RTK Query `baseUrl`
   becomes role-dependent at runtime, so every shared query needs a role switch. One
   canonical `/dashboard/suppliers` is strictly simpler than `/executive/suppliers` for one
   page and `/branchmanager/suppliers` for the other.
2. **It re-scatters authorization.** `RolePermissions` exists precisely to prevent this — its
   own docblock says *"This class holds the capability map; everything else asks it."* Route
   prefixes would encode role rules again in ~40 route lines per role tree, and the trees
   would drift (they already have: BranchManager is missing several Executive routes, which
   is what produced the original `/dashboard/product-categories` 404).
3. **Role checks already have a home.** `RequireRole` + the policies answer "who may see this"
   without encoding it in the URL. `SupplierPolicy` now delegates to
   `RolePermissions::canManageSuppliers()`, and that is the pattern to extend.

**What is worth keeping from the proposal:** the name `/api/dashboard/*` *is* misleading —
Branch Managers use it too. But renaming it is pure churn and repeats the `76dc0c4` risk.
Deprioritise behind the guard below; if it is ever renamed, it must land together with
regenerated paths and a green guard test in the same commit.

### Fix 1a — repair the 7 stale paths (immediate, mechanical)

All 7 resolve to a real route once `/admin` → `/dashboard`:

| File | Current | Correct |
|---|---|---|
| `ui/src/app/store/features/business/roles/rolesQuery.ts:6` | `/admin` | `/dashboard` |
| `ui/src/app/store/features/business/customers/customersQuery.ts:6` | `/admin/customers` | `/dashboard/customers` |
| `ui/src/app/store/features/business/workers/workersQuery.ts:6` | `/admin/workers` | `/dashboard/workers` |
| `ui/src/app/store/features/business/executive/employeeSalaryQuery.ts:6` | `/admin/employee-salary` | `/dashboard/employee-salary` |
| `ui/src/app/store/features/business/executive/employeeRemunerationQuery.ts:6` | `/admin/employee-remuneration` | `/dashboard/employee-remuneration` |
| `ui/src/app/store/features/business/executive/attendanceQuery.ts:6` | `/admin/attendances` | `/dashboard/attendances` |
| `ui/src/app/store/features/branch/attendance/attendanceQuery.ts:6` | `/admin/attendance` | `/dashboard/attendances` |

Note the last one: singular `attendance` → plural `attendances`. A blind search/replace of
`/admin` would leave that one still 404-ing.

`business/suppliers/supplierQuery.ts` is already corrected to `/dashboard/suppliers`.

**Status: applied.** All 7 rewritten; `npx tsc -b` green; no `/admin` API base remains in
`ui/src/`.

### Fix 1a-2 — `updateCustomer` was deleting customers

While verifying the above, one genuine verb bug surfaced in the same file:

`ui/src/app/store/features/business/customers/customersQuery.ts:46` sent
`method: 'DELETE'` from `updateCustomer`. `DELETE /api/dashboard/customers/{id}` resolves to
`customers.destroy`, so **editing a customer deleted it**. Changed to `PATCH`.

Do **not** "fix" the other `PUT` senders. Every `.update` route is registered via
`Route::apiResource`, which maps `update` to `match(['PUT','PATCH'])` — `PUT` works everywhere.
Verified against `routes/executive.php:35-44` (`workers`, `roles`, `suppliers`, `customers` are
all `apiResource`) and `php artisan route:list`, where all 17 `.update` routes read `PUT|PATCH`.
An earlier reading of `route:list` that suggested `PATCH`-only was a faulty regex on my side, not
a backend fact — worth remembering before trusting a one-off `grep` of route output.

### Fix 1b — stop the class of bug (the real fix)

The underlying defect is that API paths are duplicated as free-text strings in two repos with
no contract, so nothing detects drift. Add a backend test that fails when they disagree:

```
tests/Feature/FrontendApiPathsTest.php
```

- read every `baseUrl: \`${import.meta.env.VITE_BASE_URL}…\`` out of `ui/src/app/store/features/`
- substitute a concrete origin, strip the `{param}` segments each query appends
- assert a matching route exists in `Route::getRoutes()`

**Also assert the HTTP method, not just the path.** `Route::apiResource` maps `update` to
`PUT|PATCH`, so a path-only check passes while a `DELETE` sent to an update endpoint still
resolves to `destroy` — which is exactly how `updateCustomer` came to delete customers. Compare
each mutation's verb against the registered verbs for that URI.

No codegen infrastructure, no build coupling, and it catches every future rename — the check
that would have failed CI on `76dc0c4`. If the team later wants compile-time safety, generate
a typed `ui/src/lib/apiPaths.ts` from `php artisan route:list --json` and let this test assert
the generated file is current.

### Fix 1c — correct the test that encodes the bug

`AttachmentTest:168` posts to `/api/admin/customers/{id}/attachments`. The route has only ever
existed at `/api/dashboard/customers/{id}/attachments`. Fix the URL in the test. Do **not** add
an `/api/admin` alias to make it pass — that re-creates the drift in the other direction.

---

## Error 2 — username double prefix (3 failures)

**Proposal:** *the backend should prefix, so the prefix should be removed from the front end.*

**Verdict: correct, but it is necessary and not sufficient.** Two further defects sit behind
the same three failures.

`api/app/Services/UserService.php:100`
```php
'username' => "@" . ($data['username'] ?? $data['name'] ?? $data['email']),
```

1. **Unconditional prefix.** Input `@amina` is stored `@@amina`. The request is explicit that
   the value must be idempotent, so this must not depend on the client being well behaved.
   `UserService.php:61` (`signupUser`) has the identical bug.

   The third write path, `UserService.php:161` (user update), is a different bug in the same
   family: it passes the value through raw — `'username' => $validated['username'] ?? $user->username`
   — so it neither prefixes nor normalises, and a PUT can write `@@amina` or a bare `amina`
   straight to the column. It needs the same helper even though it never double-prefixes.

2. **`uniqueUsername()` does not exist.** `StoreUserRequest.php:61-66` documents it —
   *"UserService::uniqueUsername() resolves the collision instead (@jane, @jane2, …)"* — and
   greps clean across `app/` and `tests/`. It is referenced only inside that comment. This is
   why the duplicate-handle test gets **400 rather than 201**: two Aminas both become
   `@@amina`, and the column's unique index rejects the second. Removing the `@` alone will
   not fix that test.

3. **`createAccount`'s fallback chain omits `firstname`.** `signupUser:61` uses
   `$data['name'] ?? $data['firstname'] ?? $data['email']`; `createAccount:100` uses
   `$data['username'] ?? $data['name'] ?? $data['email']`. The signup form sends `firstname`
   and no `name`, so every real signup derives its handle from the **email** —
   `@amina@example.com`. The tests miss this because they post `username` explicitly.

Worth noting for the frontend side of the proposal: `SignUp.tsx` and the onboarding pages send
**no** username at all (only `AiChat.tsx` reads one), so there is no `@` in the client to strip
today. The contract just needs stating — client sends a bare handle, backend owns the `@`.

### Fix 2

Add two helpers on `UserService` and route all three write paths (`:61`, `:100`, `:161`)
through them:

```php
public static function normalizeUsername(?string $value): ?string
{
    $bare = ltrim(trim((string) $value), '@');   // ltrim, not a single @
    return $bare === '' ? null : '@' . strtolower($bare);
}

public static function uniqueUsername(string $base): string
{
    // @jane, @jane2, @jane3 … honouring the unique index as the backstop
}
```

- `ltrim(…, '@')` makes it idempotent for **any** client, which is the actual requirement.
- `createAccount` derives the base as `$data['username'] ?? $data['firstname'] ?? $data['name'] ?? $data['email']`
  so a real signup gets `@amina`, not `@amina@example.com`.
- Lower-casing is required or `unique:users,username` lets `@Jane` and `@jane` coexist.

Then keep `StoreUserRequest`'s deliberate *absence* of a `unique` rule — the comment's
reasoning is sound once the helper exists.

---

## Error 3 — `PosController` turns a 404 into a 500

`api/app/Http/Controllers/PosController.php:82-87`
```php
} catch (\Exception $e) {
    $status = $e->getCode();
    if ($status < 400 || $status > 599) { $status = 500; }
```

`PosService::checkout()` refuses another user's held sale via `firstOrFail()`
(`PosService.php:200-204`), which throws `ModelNotFoundException` **with code `0`**. The
`0 < 400` guard then rewrites it to 500.

The controller conflates two unrelated conventions: the service signals domain errors by
throwing `new Exception($msg, 422)`, while framework exceptions carry code `0`. Only the
former's code is meaningful.

Confirmed by running the test with `withoutExceptionHandling()` — still 500, proving the
response is built by this `catch`, not by the framework handler.

This affects every `firstOrFail()` reachable through this method, not just held sales.

### Fix 3

Make the status derivation explicit instead of reading `getCode()` blindly:

```php
} catch (ModelNotFoundException) {
    return response()->json(['message' => 'Not found'], 404);
} catch (\Exception $e) {
    $status = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 500;
    …
}
```

Better still, stop overloading the exception code for control flow — introduce a domain
exception (`CheckoutFailed` carrying an HTTP status) so the mapping is one `match` in one
place instead of a numeric range test repeated across the controller. Audit the sibling
controllers for the same `catch (\Exception)` + `getCode()` shape; `holdSale`,
`getHeldSales` and `deleteHeldSale` (`:104-148`) already collapse *everything* to 500/404 and
should be folded into the same change.

---

## Error 4 — `PosCheckoutTest` fixture omits `business_id`

`PosCheckoutTest::setUp()` builds the customer as:
```php
$customerUser = User::factory()->create(['business_id' => $this->business->id]);
$this->customer = Customer::factory()->create(['user_id' => $customerUser->id]);   // no business_id
```
`Sanctum::actingAs()` runs *after* that, so `BaseModel`'s `creating` hook
(`BaseModel.php:58-81`) has no ambient auth or `BusinessContext` to stamp `business_id` from —
the column lands NULL.

`CustomerCreditController::customerForCurrentBusiness()` then calls `firstOrFail()`, but
`BaseModel`'s `business` global scope (`BaseModel.php:28-50`) has already ANDed
`where business_id = X`. Its `orWhereHas('user', …)` fallback cannot rescue the row, because
NULL never satisfies the scope's own predicate. → 404.

`CustomerFactory` does not set `business_id` either, and neither does the parallel fixture in
`AttachmentTest` — which passes `business_id` explicitly and therefore passes.

**Verified:** adding `'business_id' => $this->business->id` to the fixture takes
`PosCheckoutTest` from 8 passed / 2 failed to 9 passed / 1 failed.

### Fix 4

One-line fixture change in the test. Then add `business_id` to `CustomerFactory`'s
`definition()` so every call site inherits it — a factory that produces rows which are
invisible to their own tenant's global scope is a trap, and this is the second time it has
cost a test.

---

## Error 5 — delete `ProbeHeldTest`

`tests/Feature/POS/ProbeHeldTest.php` is scratch work: one compressed method named `test_probe`,
`withoutExceptionHandling()`, and a hand-built `Sale::create()` that passes `business_id` —
which `Sale::$fillable` (`Sale.php:16`) does not include, so it is dropped and the `creating` hook cannot
stamp it without an auth context. `sales.business_id` is `NOT NULL` → SQLSTATE 23502.

Delete the file. It asserts nothing the suite does not already cover.

It does point at one real gap worth a separate ticket: `business_id` is not mass-assignable on
`Sale`, so any code creating a `Sale` outside a request (console command, queued job without
`BusinessContext`) cannot set it. `ReceiptNumberSequenceTest:105` already has a comment working
around this. Worth confirming that every non-request `Sale` creation path wraps itself in
`BusinessContext::run()`.

---

## Error 6 — persistent test DB vs absolute count assertion

`SignupTest:282` asserts `Business::count() === 0`. In isolation the class passes **19/19**;
in a full run it sees **5**.

`inventory_test` is persistent and already holds 4 leftover businesses
(`Test Whole Sallers`, `Grace Retail`, `Ibrahim Retail`, `Branchy Ltd`, created 2026-10-01 /
10:39–11:18). `RefreshDatabase` guarantees the *current test's* writes roll back — it cannot
un-pollute rows a previous run or dev session committed. Verified the count is stable at 4
before and after a `ReceiptNumberSequenceTest` run, so the current suite is not still leaking;
the dirt predates it.

Five test classes also opt out of transactions entirely:
`ReceiptNumberSequenceTest`, `UnauthenticatedResponseTest`,
`Reports/MonthlyPerformanceReportAuthTest`, `WhatsApp/TemplateRendererTest`,
`WhatsApp/NotificationCatalogueTest`.

### Fix 6

1. **Make the assertion relative** — this is the actual fix. Capture the count before the
   request and assert it did not grow:
   ```php
   $before = Business::count();
   $this->postJson(…)->assertStatus(422)->assertJsonValidationErrors('country_id');
   $this->assertSame($before, Business::count());
   ```
   A test should assert about the change it causes, not about global state it does not own.
   Grep for other absolute `assertSame(0, …)->count()` assertions and convert them the same way.
2. **Clean the database once** — `php artisan migrate:fresh` in docker, so the 4 stragglers go.
3. **Keep `ReceiptNumberSequenceTest` without transactions** — it deliberately relies on
   sequences being non-transactional, so `RefreshDatabase` would invalidate its own premise.
   Instead isolate its fixtures: `Business::firstOrCreate` on a fixed email writes permanently,
   so switch it to a per-run unique key and clean up in `tearDown`, or move it onto its own
   database.

---

## Suggested order

1. ~~**Fix 1a**~~ — **done.** 7 baseUrls rewritten, typecheck green.
2. ~~**Fix 1a-2**~~ — **done.** `updateCustomer` DELETE → PATCH.
3. ~~**Fix 1c**~~ — **done.** `AttachmentTest` URL corrected.
4. ~~**Fix 2**~~ — **done.** `normalizeUsername()` + `uniqueUsername()` added; all three write
   paths routed through them; login lookup fixed to match bare handles.
5. ~~**Fix 3**~~ — **done.** `ModelNotFoundException` → 404 in `checkout`, `holdSale`,
   `getHeldSales`.
6. ~~**Fix 4**~~ — **done.** `business_id` added to `PosCheckoutTest` fixture and
   `CustomerFactory`.
7. ~~**Fix 5**~~ — **done.** `ProbeHeldTest` deleted.
8. ~~**Fix 6**~~ — **done.** `SignupTest` assertion made relative.
9. **Fix 1b** — the guard that stops 1a recurring. Not yet started. Land it with verb
   checking (not just path checking) so it would have caught the `updateCustomer` DELETE bug.

Deliberately excluded: renaming `/api/dashboard/*` to role-prefixed groups. See the verdict
above — it is the change that caused this class of bug.