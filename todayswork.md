# Today's Work — Task 2 (Medium)

## Issue

Prevent cross-branch stock deductions in the non-POS sale flow.

The risk is in `SaleItemService`: a sale can be validated against the wrong branch context, allowing a user with access to one branch to reduce stock belonging to another branch. The review notes this as a medium-severity integrity bug because it can silently drain inventory across tenant branches if the branch check is bypassed.

## Goal

Fix the branch-scoping bug by matching the safe pattern already used in `PosService`, then verify the sale flow fails closed when branch ownership does not match.

## Implementation plan ✅

- [x] 1. Reproduce the bug with a focused regression test
  - Create or extend a sale/stock test that submits a product from Branch A while the request is scoped to Branch B.
  - Assert the request is rejected with a 403/422 and that no stock is decremented.
  - Include a second case where branch and product match, and the sale still succeeds normally.

- [x] 2. Align `SaleItemService` with the safe branch pattern from `PosService`
  - Resolve the active `business_branch_id` from the validated request or the current user.
  - Re-check the user's allowed branch scope before any stock mutation.
  - Load products using a branch-scoped query, then fail if any requested product is missing from that branch.
  - Use a locked read (`lockForUpdate()`) before decrementing stock in the same transaction to avoid race conditions.

- [x] 3. Guard against branch mismatch before decrementing stock
  - Validate every product in the request against the target branch before calculating totals or updating quantities.
  - Explicitly reject items whose `business_branch_id` does not equal the active branch ID.
  - Keep the failure inside the database transaction so no partial stock update is committed.

- [x] 4. Keep the stock mutation atomic
  - After validating quantities, decrement each product with the same branch context used to fetch it.
  - Ensure the decrement and any sale item creation occur in one transaction, mirroring the safer path in `PosService`.
  - Preserve existing sale totals, payment creation, and receipt generation logic after the stock validation passes.

- [x] 5. Add targeted verification
  - Run the focused sale/stock test that covers cross-branch mismatch.
  - Run the existing sale-related test subset to confirm no regression in normal branch sales.
  - Confirm the branch validation error is deterministic and not dependent on sales order or concurrent requests.

## Acceptance criteria

- [x] A sale request for a product outside the selected branch is rejected.
- [x] No stock is decremented on branch mismatch.
- [x] Valid branch sales continue to work without behavior change.
- [x] The implementation follows the same locking and branch validation pattern as `PosService`.

## Files to touch

- `api/app/Services/SaleItemService.php`
- `api/app/Services/PosService.php` (reference implementation)
- Relevant sale-stock feature tests in the API test suite

## Notes

This is a tightly scoped fix: do not broaden into unrelated medium items. The goal is branch safety and inventory integrity only.
