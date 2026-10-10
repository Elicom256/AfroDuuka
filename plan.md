# Plan: Fix purchase stock and pricing logic

## Objective

Fix the purchase flow so that when a branch manager or executive records a purchase of 20 phones and only 10 are available, the remaining stock stays at 10 instead of incorrectly becoming 30. Also ensure product prices are updated after that purchase.

## Step 1: Reproduce and isolate the bug

- Locate the purchase creation flow and the product stock update logic.
- Confirm the current calculation that adds purchased quantity to remaining stock instead of respecting the actual available quantity.
- Trace where the product price is updated or skipped during purchase processing.

## Step 2: Define the correct business behavior

- Starting stock example: 10 units available.
- Purchase request: 20 units.
- Correct result: stock should not jump to 30; it should remain at 10 (or be handled according to the actual available inventory rule the business expects).
- After the transaction, prices tied to the product or branch should be refreshed/updated to reflect the purchase outcome.

## Step 3: Add a failing regression test

- Write a targeted test covering the 10-available / 20-purchased scenario.
- Assert the stock remains correct after the purchase.
- Assert the price update logic is triggered once the purchase is recorded.
- Run the relevant tests to confirm the bug is reproduced before the fix.

## Step 4: Fix the stock update logic

- Inspect the service, model, or controller responsible for purchase processing.
- Replace the incorrect stock update logic with the correct inventory calculation.
- Ensure the code never inflates remaining stock beyond the real available inventory.
- If the business rule requires capped calculation or quantity validation, implement that explicitly.

## Step 5: Fix the price update logic

- Find the product price sync/update code path used after purchase.
- Ensure price fields are recalculated or refreshed when purchase data is saved.
- Confirm the updated values are persisted in the database and exposed in API/UI responses.

## Step 6: Validate the purchase flow end-to-end

- Test a normal purchase with enough stock.
- Test the edge case where requested quantity exceeds available stock.
- Confirm the UI/API returns the corrected remaining quantity and updated pricing.

## Step 7: Run targeted verification

- Run the smallest relevant test suite or endpoint checks for this functionality.
- Check for regressions in nearby purchase and inventory features.
- Fix any failing validations before moving on.

## Step 8: Review and prepare for merge

- Confirm the patch is limited to the inventory and pricing logic.
- Ensure naming, comments, and logic read clearly for future maintenance.
- Check for any related branch or product update code paths that should receive the same fix.

## Step 9: Commit and push to the current branch

- Commit the fix with a clear message describing the purchase stock and price update correction.
- Push the branch to the current remote branch.
- Confirm the branch is updated and ready for review.

## Expected outcome

The purchase process will correctly reflect inventory and price changes, and the stock update will no longer incorrectly increase available quantity in the scenario described above.
