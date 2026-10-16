# Task: Implement the Laravel Backend Sync Contract for AfroDuuka Offline-First POS

## Project context

AfroDuuka is a multi-tenant inventory management SaaS.

- Frontend: React + Vite + TypeScript.
- Backend: Laravel API.
- Database: PostgreSQL.
- Authentication: Laravel Sanctum, subject to the existing implementation.
- Tenant isolation: businesses and their branches, with `tenant_id` / `business_id` / `business_branch_id` conventions already established in the application.
- The frontend now has an offline persistence foundation using Dexie and IndexedDB.

The next task is to implement the backend foundation required to safely synchronize queued offline operations from the frontend.

## Primary objective

Implement a secure, idempotent Laravel sync API that can accept offline operations and process them without duplicating sales, corrupting inventory, or generating duplicate financial records.

**Inspect the existing application first. Do not assume the database schema, existing sale workflow, models, relationships, stock logic, or financial calculations.**

Review:

- Existing API routes and controllers.
- Sanctum authentication and authorization.
- Business and branch ownership relationships.
- Sales, sale items, purchases, inventory, stock movements, cash flows, and payment models.
- Existing service classes, events, listeners, and queued jobs.
- Existing transaction boundaries and inventory locking mechanisms.
- Existing migrations, factories, and tests.
- Any frontend offline operation types and payload structures available in the repository.

Preserve established business rules and reuse the existing sales creation logic wherever possible.

## 1. Define the sync API contract

Design and implement a version-appropriate authenticated endpoint, preferably:

`POST /api/sync/batch`

Use the project's existing route prefixes and response conventions.

Accept a bounded list of operations. Initially support:

- `sale`
- `inventory`
- `customer`

Each operation should have:

- `operation_id`: stable, client-generated UUID.
- `type`: supported operation type.
- `payload`: validated, type-specific data.
- `created_at`: client timestamp for audit context, never authoritative for server ordering or financial calculations.

Reject malformed requests, unsupported operation types, oversized batches, and invalid payloads.

Define a clear response for each operation, including:

- `operation_id`
- `status`: `synced`, `duplicate`, `conflict`, or `failed`, as appropriate.
- `server_id`, when available.
- A stable machine-readable error code and safe explanatory message for rejected operations.

One operation's failure must not prevent unrelated operations in the same batch from being processed. Process operations independently, with an atomic database transaction per operation.

Do not return success until the corresponding database transaction has committed.

## 2. Implement durable idempotency

Create an appropriate migration and persistence model for tracking processed sync operations.

Inspect the existing schema before choosing fields. The record should support, as appropriate:

- Operation UUID.
- Business/tenant scope.
- Authenticated actor or device context where appropriate.
- Operation type.
- Processing status.
- Resulting server resource identifier.
- Stored result sufficient to return the original outcome on retries.
- Processing timestamps and relevant error information.

Add a database-enforced unique constraint on the correct idempotency scope, such as `(business_id, operation_id)` if consistent with the existing domain model.

Requirements:

- Replaying a successfully processed operation must never create another sale, stock movement, payment, or cash-flow record.
- Return the stored result of an already-processed operation.
- Handle concurrent duplicate requests safely using PostgreSQL constraints and appropriate transaction handling.
- Never rely exclusively on an application-level existence check.
- Ensure a failed operation can be retried according to an explicit, safe retry policy.
- Do not permanently mark an operation successful before its business transaction commits.
- Prevent a crash or race condition from leaving an operation permanently stuck in an ambiguous processing state.

If an existing idempotency mechanism already meets these requirements, reuse or extend it rather than introducing a competing implementation.

## 3. Implement offline sales processing

Integrate queued sale operations with the existing authoritative sales workflow.

Within one database transaction:

1. Authenticate the caller and establish the authorized business context.
2. Validate that the selected branch belongs to the authorized business.
3. Validate every product and any required variant, unit, or pricing reference.
4. Validate quantities, discounts, payment information, and other required sale fields.
5. Apply the existing pricing and sales business rules.
6. Lock relevant inventory records using appropriate PostgreSQL row-level locking where necessary.
7. Apply stock movements and reject or reconcile insufficient stock according to existing business rules.
8. Create the sale and its sale-item records.
9. Create associated cash-flow, payment, or audit records using existing application conventions.
10. Persist the successful idempotency result.
11. Commit the transaction.

All database effects for a sale must succeed or roll back together.

Do not bypass existing service classes or duplicate financial calculations unnecessarily.

### Offline price handling

Offline sales may have been recorded using a locally cached price that differs from the current server price.

Inspect the existing pricing model and establish an explicit, auditable policy for this situation. If historical offline pricing is supported, validate it against appropriate business rules and preserve the actual agreed sale price. Otherwise, return a clear conflict requiring reconciliation.

Never silently replace the customer's recorded sale price with a different price.

Never trust arbitrary client-supplied totals, profit, cost price, stock balances, or financial calculations. Recalculate authoritative values on the server.

### Inventory safety

Never blindly apply a client-supplied absolute stock quantity as a last-write-wins update.

Use the existing stock movement and inventory rules. Distinguish a sale recorded offline from a stock adjustment operation. Concurrent sales from different devices must not create negative stock or duplicate movements contrary to the application's rules.

If the system cannot safely accept a sale due to a stock conflict, preserve a clear conflict result for reconciliation rather than silently losing the operation.

## 4. Implement inventory and customer operations

For inventory operations:

- Inspect the existing inventory adjustment workflow.
- Accept only supported, explicitly authorized adjustments.
- Validate product, branch, quantity, and reason.
- Record auditable stock movements.
- Never accept arbitrary absolute balances from the client.
- Reject conflicting adjustments when they cannot be safely reconciled.

For customer operations:

- Validate required customer fields and tenant ownership.
- Handle repeated submissions safely.
- Avoid creating duplicates where an existing customer identity or domain-specific uniqueness rule can identify the same record.
- Return the resulting server customer ID so the frontend can reconcile temporary local identifiers.

Do not invent customer matching rules that could incorrectly merge different customers.

## 5. Tenant isolation and security

Security is mandatory.

- Apply existing Sanctum authentication middleware.
- Resolve the authorized business using the existing authorization model.
- Validate branch and product ownership server-side.
- Do not trust client-provided `business_id`, / `business_branch_id`, tenant IDs, user IDs, roles, or permissions.
- Prevent cross-tenant access through guessed UUIDs or resource IDs.
- Apply existing policies and permissions for sales, inventory adjustments, and customer creation.
- Validate every operation independently.
- Add appropriate request-size and batch-size limits.
- Avoid logging credentials, access tokens, or unnecessary personal data.
- Ensure operation IDs cannot be replayed across unauthorized tenant contexts.

Do not weaken authentication to make offline synchronization easier. Offline operations must still authenticate when they reach the server.

## 6. Add observability and recovery

Provide useful logs and operational visibility for:

- Successful synchronization.
- Duplicate requests.
- Validation failures.
- Inventory and pricing conflicts.
- Transient database failures.
- Unexpected processing exceptions.

Use structured logging and safe error messages.

Keep the processing flow recoverable after request timeouts, client disconnects, and retries. If the frontend retries after a timeout, the backend must return the original committed result when the operation already succeeded.

Do not require a background job merely to process a small synchronous batch unless the existing architecture justifies it. If jobs or outbox events are needed for external side effects, ensure those side effects are not duplicated when a request is retried.

## 7. Database design and migrations

Create only the migrations needed for the sync contract.

- Follow existing naming conventions and database types.
- Add database constraints and indexes appropriate to the idempotency scope and query patterns.
- Keep migrations compatible with the project's PostgreSQL environment.
- Do not delete or reset existing data.
- Do not introduce a second inventory ledger or financial ledger if the application already has one.
- Ensure rollback and error handling are safe.

Do not automatically run destructive migrations against a remote or production database.

## 8. Testing requirements

Add tests using the project's existing testing framework.

Cover at least:

1. Authenticated, valid operation.
2. Unauthenticated request rejection.
3. Cross-tenant and cross-branch access rejection.
4. Duplicate sale operation returns the original result.
5. Concurrent duplicate requests do not create duplicate records.
6. Sale and associated financial/inventory records roll back together on failure.
7. Insufficient stock or pricing conflict returns an appropriate result.
8. One failed operation does not block successful unrelated operations in the same batch.
9. Invalid payloads and unsupported operation types are rejected.
10. Retry after a successful commit but simulated client timeout does not duplicate effects.
11. Customer retries do not create unintended duplicates.
12. Inventory adjustments preserve the existing audit and stock movement rules.

Use isolated test data and the project's supported test database. Do not run destructive tests against production data.

## 9. Deliverables

After implementation, report:

- The actual endpoint URL and request/response schema.
- Files created and modified.
- Migrations added.
- How idempotency and concurrent duplicate requests are handled.
- How stock, pricing, and financial consistency are enforced.
- Which existing services and models were reused.
- Tests executed and their actual results.
- Any unresolved frontend/backend payload mismatches.
- Any remaining limitations before production deployment.

## Implementation discipline

Implement in small, reviewable steps. Do not rewrite the entire sales system or perform unrelated refactoring.

If the existing schema or business rules prevent a safe implementation, explain the specific blocker and implement only the safe parts.

Do not invent endpoints, bypass validation, return fake success, or claim synchronization is complete without tests demonstrating the actual behavior.
