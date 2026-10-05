# Receipt Design Match
## Issue
The receipt at http://localhost/dashboard/pos after a sale under pos route is designed differently from the receipt at 
http://localhost/dashboard/receipts/2

## Solution
All the receipts no matter where they appear/pre-appear should have an identical design. They should be designed as that at
http://localhost/dashboard/receipts/2

## Status — done

### Root cause
The till had its own receipt, written separately from the receipt page and never sharing a
component with it. It had drifted in three ways at once:

- different structure — a plain `product x qty` list instead of the products table
  (no SKU, unit price, discount or line total per row);
- fewer fields — the till showed subtotal, tax and total, and omitted receipt number,
  cashier, payment method, amount paid and change given, all of which the receipt page
  shows;
- a different data source — it rendered from the sale response rather than the receipt.

So the same sale read two different ways depending on where it was sold.

### Fix
1. **`ReceiptView.tsx` (new)** — the receipt document, extracted from `ReceiptDetail`
   unchanged. Pure presentation: it fetches nothing, so every surface that shows a
   receipt shows this one.
2. **`ReceiptDetail`** now renders `<ReceiptView />`, keeping only its own chrome (back
   link, Open PDF, Download PDF).
3. **`PosReceiptModal.tsx` (new)** — the till's receipt surface, rendering the same
   `ReceiptView`. Extracted from `PosPage` so the guarantee is testable; while it was
   inline, no test could reach it.
4. **`PosPage`** renders `<PosReceiptModal receiptId={completedSale.receipt?.id} />`.

The till now fetches the receipt from `GET /receipts/{id}` rather than assembling it from
the sale. `PosService::checkout()` loads `receipt.items` but not `receipt.user` or
`receipt.customer`, both of which the shared design renders, so the sale payload was never
sufficient. The modal keeps its own Print and New Sale buttons — those are till
workflow, not part of the document.

### Also fixed
`ReceiptDetail` guarded on the payload being truthy, so an endpoint answering `{}` passed
the check and rendered a receipt-shaped card with no number, no items and a zero total —
indistinguishable from a real sale worth nothing. It now checks `receipt?.id`.

### Tests
`ui/src/app/routes/receiptDesign.test.tsx` — 7 tests. The design is pinned as a list of
the labels it must contain, asserted on rendered text rather than class names:

- the receipt page renders every field;
- `ReceiptView` renders the design standalone, which is what pins every other surface;
- the till receipt renders the same field list;
- the till no longer falls back to the bare subtotal/total layout;
- a missing receipt reports itself instead of rendering an empty document.

Mutation-checked: reverting the till to its old markup fails two tests. The first version
of this suite did **not** — it rendered the receipt page and the shared component but never
POS, so it passed with the defect live. That is why the till surface was extracted.

### Verification
- `cd ui && npx tsc --noEmit` → clean
- `cd ui && npx vite build --mode development` → built
- `cd ui && npx vitest run` → **82 passed** across 6 files
- `docker compose exec -T backend php artisan test` → **717 passed** (API untouched)

### Not covered
The equivalence is asserted at the component boundary, so it holds for these surfaces
under jsdom. Worth one manual look in a browser at the till after a sale and at
`/dashboard/receipts/2` for the visual result, and a real print, since `window.print()`
output is not something these tests exercise.
