# Item 5 — Authorization and Policy Coverage: Reality Check and Plan

**Date:** 2026-10-04
**Verdict:** item 5 is **partially done and marked complete in error**. The work it
describes was real and is genuinely in the tree. The audit that concluded it was finished
was wrong about the size of the remaining hole, so the item is not closed.

---

## 1. What the review claims

`review.md` item 5 ends with:

> With both fixed, **19 mutating endpoints remain without a role or policy gate**, and
> each was reviewed and confirmed intentional: `login`/`signup` are public by design,
> `logout` only revokes the caller's own tokens, the four notification endpoints are
> scoped to `user_id = Auth::id()`, `POS` cart/held-sale routes are the seller's own floor
> actions, and `BusinessDebitController::pay` records money already committed. Every
> `DELETE` is covered centrally.

The claim is not that zero work remains. It is that the remainder was enumerated, and
that every item in it was looked at and accepted. That second half does not hold.

---

## 2. Verified as genuinely done

Checked in the tree, not taken on trust:

| Claim | Reality |
| --- | --- |
| Role management requires an elevated role | True. `StoreRoleRequest`/`UpdateRoleRequest` authorize on `RolePermissions::isElevated()`. |
| Catalogue writes require catalogue permission | True. `StoreProductRequest`, `StoreProductCategoryRequest`, `ProductPolicy@update`. |
| `BlockRestrictedRoleActions` uses the `canDelete()` allowlist | True, `app/Http/Middleware/BlockRestrictedRoleActions.php:56`. |
| Central DELETE coverage is real | True. Appended to the whole `api` group, `bootstrap/app.php:70`, so it is not per-route and cannot be forgotten by a controller. |
| Missing user passes through for a 401, not a 403 | True, `BlockRestrictedRoleActions.php:52`. The SPA's `authListener` depends on this. |
| `StockTransferController::update` exists and is draft-only | True, with regression tests. |
| `PurchaseController::update`/`destroy` refuse explicitly | True, `PurchaseController.php:135`. |
| `AiController::chat` held to elevated roles | True, with a test. |
| Regression tests exist and pass | True — `MutatingEndpointAuthorizationTest` (770 lines), `AuthorizationPolicyCoverageTest`, `BlockRestrictedRoleActionsTest`, `RouteModelBindingTest`, `RequireRoleMiddlewareTest`. |

The suite is green: **602 passed, 1802 assertions**, run inside the backend container
against the bind-mounted source. The review's `593 / 1764` is stale, not wrong — later
commits added tests.

So the item is not a fabrication. The central delete allowlist is a genuinely good fix,
and the 401-not-403 reasoning is correct. The problem is narrower and more specific than
"the work was not done": **the audit that certified the remainder as reviewed did not see
most of the remainder.**

---

## 3. The actual gap

I re-ran the audit the way the review says it was run, but resolving gates properly: route
middleware, in-controller `abort_unless` / policy calls, *helper methods* those controllers
delegate to (`ensureSiteAdmin()`), and each injected `FormRequest::authorize()` body. The
first pass of my own audit was also wrong in the same direction the review describes —
counting `auth:sanctum` as a gate — so the numbers below are from the corrected version.

```
174 mutating routes: 126 gated, 48 ungated
```

The review accounts for 19. **29 were never looked at.**

The reason is a specific, repeatable mistake, and it is the same class of error the review
caught itself making once. A `FormRequest::authorize()` that reads:

```php
return Auth::check();
```

was treated as a gate. It is not. It is authentication. Across this codebase there are 96
such `authorize()` bodies. For most modules a route-level `role` group covers them. For the
modules below, nothing covers them.

I confirmed the top three by running them, as an **Operations** user — the till role that
item 5 deliberately fences out of catalogue authoring and out of every delete:

| Endpoint | Result |
| --- | --- |
| `POST /api/payment-gateways` | `201 Created` — wrote `provider: mtn_momo`, `api_key`, `api_secret`, `webhook_secret` |
| `POST /api/currency-rates` | `201 Created` — wrote `UGX→USD rate 9999`, `source: attacker` |
| `POST /api/finances/business-debits` | `201 Created` — wrote a 500,000 debit against a supplier |

All three persisted. The first one is the serious one: that is the record of where live
mobile-money payments are routed and how inbound webhooks are authenticated. An Operations
account — the role this review defines as "runs the day-to-day floor, does not author the
catalogue, does not remove records" — can rewrite it.

### The 29, grouped by what they can reach

**Payment and provider credentials — highest severity**

| Route | Why it matters |
| --- | --- |
| `POST/PUT /api/payment-gateways` | MTN MoMo / Airtel / Flutterwave / Pesapal keys and webhook secret. Money routing. |
| `POST/PUT /api/whatsapp` | Provider token and business phone number. |
| `POST /api/whatsapp/test-message` | Sends a real outbound message. Metered. |

**Money**

| Route | Why it matters |
| --- | --- |
| `POST/PUT /api/currency-rates` | Every multi-currency total and report derives from this. |
| `POST/PUT /api/finances/business-debits` | Tenant-wide finance ledger. |
| `POST/PUT /api/finances/business-credits` | Tenant-wide finance ledger. |
| `POST /api/finances/adjustments` | Cash-flow writes. |
| `POST /api/finances/customers/{customer}/credit-payments` | Records customer money received. |
| `POST /api/finances/cash-drawers/open`, `POST .../close` | Cash sessions and variance. |

**Stock destruction**

| Route | Why it matters |
| --- | --- |
| `POST /api/product-losses` | `InventoryService::writeOff()` decrements quantity. The service validates the *reason* and the *quantity*, never the *role*. |
| `POST /api/returns/sale-returns` | Stock back in, refund out. |
| `POST /api/returns/purchase-returns` | Stock back out to a supplier. |

**Trade documents and purchasing**

| Route | Why it matters |
| --- | --- |
| `POST/PUT /api/purchases/branch-purchases` | A purchase commitment to a supplier. Note `PurchaseOrderController` *is* gated inline; this one is not. |
| `POST/PUT /api/sale-orders` | Quoted trade documents. |

**Configuration**

`POST/PUT /api/printers`, `POST/PUT /api/reorder-rules`, `POST/PUT /api/report-exports`.

### Deliberately open — confirmed correct, leave alone

`login`, `signup`, `updateProfile`, the four notification endpoints (all self-scoped on
`user_id`), `todos` store, `POST /api/sales/branch-sales` and its `PUT` (the till has to be
able to ring up a sale; completed-sale immutability from item 3 already guards the `PUT`),
and the POS floor routes. `BusinessDebitController@pay` stays open for the reason the review
gives, which is sound.

### Not a live hole, but worth closing

`UpdateExpenseRequest::authorize()` returns bare `true`. Expenses are currently covered by
the `role` group at `routes/api.php:94`, so nothing is exposed today — but the request would
authorize anything if that group were ever dropped. Change it to the same capability check
its sibling requests use.

### The DELETEs need nothing

All eight ungated `DELETE` routes in the table above are already covered centrally by
`BlockRestrictedRoleActions`. That part of item 5 is done properly.

---

## 4. Plan

Ordered so that each step is verifiable on its own and the money paths close first.

**Step 1 — add the missing capabilities to `RolePermissions`.**
Nothing new is needed conceptually; the map already has the right shape. Add the two that
have no honest home yet: `canManagePaymentConfig()` for gateways, currency rates and
WhatsApp credentials, and `canManageCashDrawer()` for open/close. Both should resolve to
`canManageBranch()` — branch managers run their own branch's till, and the branch scope
already confines them. Keep the docblocks explaining *why*, since the existing ones are
what made this file trustworthy.

**Step 2 — close the payment and credential paths.** Payment gateways, currency rates,
WhatsApp config and test-message. Route-level `role` where the group already exists, inline
`abort_unless` where it does not — matching what `PurchaseOrderController` already does.

**Step 3 — close the money paths.** Business debits, business credits, adjustments,
customer credit payments, cash drawers. Note the asymmetry the review already reasoned
about: `pay` stays open, `store` and `update` do not.

**Step 4 — close the stock-destruction paths.** Product losses, sale returns, purchase
returns. The gate belongs at the controller, not in `InventoryService::writeOff()`, because
that service is also reached from legitimate stock-count paths that Operations must keep.
`canModifyStock()` is the existing expression of this.

**Step 5 — close trade documents and config.** Purchases, sale orders, printers, reorder
rules, report exports. Reorder rules and report exports are purchasing/report automation
and belong beside `canCreatePurchaseOrder` and `canManageReports`.

**Step 6 — harden `UpdateExpenseRequest`.** Replace `return true` with the capability check
its siblings use. Defensive, not urgent.

**Step 7 — pin it all with tests.** Extend `MutatingEndpointAuthorizationTest`, which is
already the right home: one Operations-cannot test and one permitted-role-can test per
group, asserting the row did not change on the refusal. Add a regression that an Operations
user cannot write a payment gateway — that is the one worth naming in the suite forever.

**Step 8 — make the audit mechanical and keep it.** The reason this item drifted is that
the audit was a reading exercise. Fold the resolver from this review into a test that walks
the real route table, resolves route middleware, helper-method gates and
`FormRequest::authorize()` bodies, and fails when a mutating route appears with none. It
needs an explicit allowlist of the intentional exceptions above — an unaudited new endpoint
should break CI rather than wait for the next person to notice.

**Step 9 — re-run the full suite** and update `review.md` item 5 with the corrected count
and the evidence. Only then is the item closed.

---

## 5. Notes for whoever picks this up

- Run tests with `docker exec afroduuka-backend-1 php artisan test`. `php artisan test`
  on the host cannot resolve `pgsql` and fails 552 of 602 — that is an environment
  problem, not a code problem.
- `--parallel` does not work; ParaTest is not installed.
- When auditing by hand, the two traps that produced the wrong answer both times:
  `Auth::check()` is not a gate, and a controller that delegates to a private
  `ensure*()` helper *is* gated even though the helper is not in the method body.
  `SuperAdminBusinessController` is the example of the second.