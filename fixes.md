# Bug Fixing Plan for DuukaFlow

## Goal

Stabilize the core retail/inventory system before adding more features. The primary objective is to make stock, money, and access rules trustworthy enough for real business use.

## Guiding Principle

Fix the system in this order:

1. Safe inventory behavior
2. Safe financial behavior
3. Safe access control and auditing
4. Safe offline sync and recovery
5. Then feature completion

---

## Phase 0: Define the release-blocking rules

Before code changes, define the business invariants that must never be violated.

### Invariants

- Stock cannot go below zero unless the business explicitly allows negative stock.
- Every stock change must be traceable to a purchase, sale, return, transfer, adjustment, or damage record.
- Every financial transaction must be tied to a valid sale, refund, debt, expense, or cash movement.
- A user cannot access or edit records outside their branch/role permissions.
- Every critical change must be logged with timestamp, actor, and reason.
- Offline transactions must reconcile cleanly when connectivity returns.

### Required outputs

- A clear list of valid stock movement types
- A list of taxable vs non-taxable items
- A clear branch and role permission matrix
- A reconciliation policy for offline data sync

---

## Phase 1: Fix inventory integrity issues

These are the most important bugs because they affect actual business operations.

### 1.1 Negative stock and oversell protection ✅

Bug: sales, transfers, or refunds can push stock below zero or miscalculate remaining quantity.

Tasks:

- Add server-side stock validation on every sales/return/transfer action.
- Reject invalid stock operations with explicit error messages.
- Apply atomic stock updates inside database transactions.
- Prevent stock changes from being partially saved.

Acceptance criteria:

- A sale cannot reduce stock below zero.
- A transfer cannot move more units than available at source.
- Failed transactions leave stock unchanged.

### 1.2 Duplicate stock movement records ✅

Bug: retries or partial failures can create duplicate stock entries.

Tasks:

- Ensure each stock movement is idempotent.
- Add transaction IDs or unique keys for each operation.
- Check for duplicate execution before recording movement entries.

Acceptance criteria:

- Retrying a failed request does not double-count stock.
- Logs reflect only one final movement per transaction.

### 1.3 Purchase vs received stock mismatch ✅

Bug: purchased stock may be recorded without matching actual stock arrival or receipt validation.

Tasks:

- Separate purchase orders from stock receipt validation.
- Require receiving workflow or receipt confirmation.
- Link each stock increase to supplier purchase/receipt records.

Acceptance criteria:

- Stock only increases after valid receipt or validation.
- Supplier and stock data remain traceable.

### 1.4 Damaged/lost/expired stock handling ✅

Bug: inventory loss is not formally tracked, leading to inaccurate stock counts.

Tasks:

- Add a dedicated stock adjustment or write-off flow.
- Support damaged, expired, and lost items with reason codes.
- Record these changes as stock movement types, not silent deletions.

Acceptance criteria:

- Product stock can be reduced with a valid reason.
- Loss records are auditable.

---

## Phase 2: Fix financial correctness issues

Financial errors are business-critical and must be addressed before any broader feature release.

### 2.1 Tax calculation and rounding ✅

Bug: inconsistent tax totals can cause undercharging or overcharging.

Tasks:

- Centralize tax computation logic in one reusable service.
- Validate rounding rules per product and tax category.
- Ensure tax totals match subtotal and grand total exactly.

Acceptance criteria:

- Taxes are consistent across all product types.
- Totals do not drift because of floating-point issues.

### 2.2 Cash reconciliation and drawer mismatch ✅

Bug: cash totals from POS transactions may not match final drawer balance.

Tasks:

- Track opening cash, sales, refunds, expenses, and closing cash separately.
- Reconcile each shift with fixed rules.
- Reject closing shift if mismatched beyond allowed threshold.

Acceptance criteria:

- Drawer mismatch is detectable immediately.
- A cash close cannot succeed if totals do not reconcile.

### 2.3 Debt and credit balance accuracy ✅

Bug: customer credits and debt balances may be miscalculated after payments or refunds.

Tasks:

- Define the debt lifecycle clearly: issued, paid, partially paid, overdue, settled.
- Recalculate balances from transaction ledger instead of cached values when needed.
- Add a ledger audit trail for balances.

Acceptance criteria:

- Customer balances always match underlying transactions.
- Refunds and partial payments adjust balances correctly.

### 2.4 Sales/refunds/cancelation consistency ✅

Bug: cancelled orders, partial returns, and failed transactions can create financial mismatch.

Tasks:

- Define consistent states for sale lifecycle: pending, paid, refunded, cancelled, failed.
- Ensure refunds do not re-increase stock or revenue incorrectly.
- Block invalid finalization after cancellation or payment failure.

Acceptance criteria:

- A cancelled sale does not appear as revenue.
- A refund clears the right amount from customer balance and stock.

---

## Phase 3: Fix access control and auditability

This is where many SaaS projects fail silently if not treated seriously.

### 3.1 Branch-level permission enforcement ✅

Bug: users may view or modify records outside their assigned branch.

Tasks:

- Enforce branch scoping in all inventory, sales, users, and reports endpoints.
- Restrict editing actions to allowed roles.
- Validate branch ownership before mutation.

Acceptance criteria:

- A branch user cannot view another branch’s stock or reports.
- Sensitive finance actions require higher privileges.

### 3.2 Audit trail coverage ✅

Bug: key changes cannot be explained later.

Tasks:

- Log all stock, pricing, tax, payment, refund, and user changes.
- Store who changed it, when, and from where.
- Make audit records readable to authorized staff.

Acceptance criteria:

- A suspicious stock change can be traced to a user and timestamp.
- Audit logs exist for critical operations.

### 3.3 Sensitive report protection ✅

Bug: financial records or customer data may be exposed beyond intended roles.

Tasks:

- Restrict access to financial summaries, debt reports, and cash actions.
- Add role-based visibility rules for exports and PDFs.

Acceptance criteria:

- Only allowed roles can view finance and cash status.

---

## Phase 4: Fix offline-first and sync reliability

This is important for POS and field sales workflows.

### 4.1 Offline transaction sync

Bug: pending transactions may conflict once connectivity resumes.

Tasks:

- Create a local transaction queue with unique local IDs.
- Use server-side idempotent sync endpoints.
- Define conflict resolution rules for updates of same item during offline period.

Acceptance criteria:

- Offline transactions sync accurately without double-counting.
- Conflicting records are safely resolved or flagged.

### 4.2 Retry and failure handling

Bug: queued jobs or sync operations fail silently or leave partial updates.

Tasks:

- Add retry policies for failed exports, notifications, and sync jobs.
- Add dead-letter queues or explicit failure logs.
- Ensure partial queue processing does not create inconsistent records.

Acceptance criteria:

- Failed actions are retried or clearly reported.
- Critical financial operations do not silently fail.

---

## Phase 5: QA and verification before release

No bug fix should be considered complete without real verification.

### Required testing

- Unit tests for tax calculations, stock validation, and debt logic
- Integration tests for purchase, sale, refund, and stock transfer flows
- Role-based permission tests for branch restrictions
- Offline sync reconciliation tests
- Cash drawer reconciliation tests
- Audit log verification tests

### Release gate

Do not move to feature work until these are green:

- all stock movement tests pass
- all financial calculation tests pass
- branch permission tests pass
- offline sync tests pass
- backup and restore test passes

---

## Priority Order

### Critical (fix first)

- Negative stock and oversell prevention
- Stock movement duplication prevention
- Tax and pricing correctness
- Cash reconciliation
- Branch permission enforcement
- Audit logging

### High

- Damaged/lost/expired stock handling
- Debt balance validation
- Offline sync reconciliation
- Backup and restore process

### Medium

- Reports and export QA
- Return/cancelation cleanup
- Data cleanup scripts for historical inconsistencies

---

## Recommended execution plan for the next sprint

### Sprint 1

- Phase 0 rule definition
- Negative stock prevention
- Transaction idempotency
- Stock movement audit trail

### Sprint 2

- Tax calculation fixes
- Cash reconciliation
- Customer debt balance fixes
- Branch permissions

### Sprint 3

- Offline sync and queue reliability
- Backup/restore flow
- Final reporting QA

---

## Final note

This project is not at the stage where “feature building” should outrun “trust-building.” The correct first objective is to make the business rules safe, consistent, and auditable, then expand the app from there.
