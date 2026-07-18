# Refactor Plan: Replace Cart with Sale in POS

## Goal
Remove the `HeldCart` model and all cart-related logic. Instead, POS will create Sales directly — using `status='held'` for held sales and `status='completed'` for checked-out sales.

---

## Step 1: Edit Sales Migration Directly

Since we're in development, edit the existing migration files directly:

- **`2026_05_23_161549_create_sales_table.php`**: Add `'held'` to the status enum → `['held', 'pending', 'completed', 'cancelled']`, and add `user_id` column
- **`2026_07_17_000001_create_held_carts_table.php`**: Add `Schema::dropIfExists('held_carts')` to the `down()` method (or just delete the file and its migration record)

## Step 2: Update Sale Model

- Add `user_id` to `$fillable`
- Add `user()` BelongsTo relationship

## Step 5: Update PosService

Replace cart-based methods with sale-based methods:

- **`holdCart()`** → Create a Sale with `status='held'` and `user_id`, create SaleItems for each item
- **`getHeldCarts()`** → Query Sales with `status='held'`, `user_id`, `business_branch_id`
- **`resumeCart()`** → Fetch a held Sale by ID with its saleItems
- **`deleteHeldCart()`** → Delete (or cancel) a held Sale
- **`checkout()`** → Already creates a Sale, but now also handle completing a previously held sale (change status from 'held' to 'completed')
- Keep `validateCart()` as-is (useful for frontend validation)
- Remove `holdCart()`, `getHeldCarts()`, `resumeCart()`, `deleteHeldCart()` methods
- Add new methods: `holdSale()`, `getHeldSales()`, `resumeHeldSale()`, `deleteHeldSale()`, `completeHeldSale()`

## Step 6: Update PosController

- Replace `holdCart()` → `holdSale()`
- Replace `getHeldCarts()` → `getHeldSales()`
- Replace `resumeCart()` → `resumeHeldSale()`
- Replace `deleteHeldCart()` → `deleteHeldSale()`
- Keep `searchProducts()`, `searchCustomers()`, `validateCart()`, `checkout()`
- Update `checkout()` to optionally accept a `sale_id` to complete a held sale

## Step 7: Update Routes (pos.php)

Replace cart routes with sale routes:
- `POST /cart/hold` → `POST /sales/hold`
- `GET /cart/held` → `GET /sales/held`
- `GET /cart/resume/{id}` → `GET /sales/held/{id}`
- `DELETE /cart/held/{id}` → `DELETE /sales/held/{id}`
- Keep `POST /cart/validate`
- Keep `POST /checkout`

## Step 8: Update Form Requests

- Remove `PosHoldCartRequest.php` (no longer needed)
- Update `PosCheckoutRequest.php` to optionally accept `sale_id` for completing a held sale

## Step 9: Update PosService

- Remove `use App\Models\HeldCart;`
- Remove `holdCart()`, `getHeldCarts()`, `resumeCart()`, `deleteHeldCart()` methods
- Add `holdSale()` - creates Sale with status='held' and SaleItems
- Add `getHeldSales()` - fetches held sales for current user/branch
- Add `resumeHeldSale()` - fetches a single held sale
- Add `deleteHeldSale()` - cancels/deletes a held sale
- Update `checkout()` to optionally accept `sale_id` to complete a held sale

## Step 10: Update Frontend posQuery.ts

Replace cart endpoints with sale endpoints:
- Keep `validateCart` mutation
- Remove `holdCart`, `getHeldCarts`, `resumeCart`, `deleteHeldCart`
- Add `holdSale`, `getHeldSales`, `resumeHeldSale`, `deleteHeldSale`
- Update tag types from `'HeldCarts'` to `'HeldSales'`

## Step 11: Update Frontend PosPage.tsx

- Replace `holdCart` → `holdSale`
- Replace `getHeldCarts` → `getHeldSales`
- Replace `deleteHeldCart` → `deleteHeldSale`
- Replace `resumeCart` → `resumeHeldSale`
- Update the held carts panel to show held sales instead
- Update the hold/resume logic to work with held sales (Sale with status='held')
- The cart state in the frontend remains the same (it's local state), but "hold" now creates a held Sale in the backend
- "Resume" now fetches a held Sale and populates the cart from its saleItems

## Step 11: Update Frontend PosPage.tsx

- Update imports to use new query hooks
- Replace `handleHoldCart` to call `holdSale` instead
- Replace `resumeCart` to call `resumeHeldSale` and map saleItems to cart items
- Replace `handleDeleteHeldCart` to call `deleteHeldSale`
- Update the held carts panel to show held sales (with sale ID, status, items count)
- Update keyboard shortcut F8 to hold (create held sale)

## Step 12: Remove/Update Test File

- Update or remove `api/tests/Feature/POS/PosHeldCartTest.php`

## Step 13: Add `held_at` timestamp to sales table (optional)

- Consider adding a `held_at` timestamp to track when a sale was held (for display purposes)

## Summary of Files to Modify

| File | Action |
|------|--------|
| `api/database/migrations/2026_05_23_161549_create_sales_table.php` | Edit directly: add `'held'` to status enum, add `user_id` column |
| `api/database/migrations/2026_07_17_000001_create_held_carts_table.php` | Delete the file (dev mode, no new migration needed) |
| `api/app/Models/HeldCart.php` | **Delete** |
| `api/app/Models/Sale.php` | Add `user_id` to fillable, add `user()` relationship |
| `api/app/Http/Controllers/PosController.php` | Replace cart methods with sale methods |
| `api/app/Http/Requests/PosHoldCartRequest.php` | **Delete** (or repurpose as PosHoldSaleRequest) |
| `api/app/Services/PosService.php` | Remove HeldCart logic, add pending sale logic |
| `api/routes/pos.php` | Replace cart routes with sale routes |
| `api/tests/Feature/POS/PosHeldCartTest.php` | Update or delete |
| `ui/src/app/store/features/branch/pos/posQuery.ts` | Replace cart endpoints with sale endpoints |
| `ui/src/app/pages/dashboards/shared/pos/PosPage.tsx` | Update to use new sale-based endpoints |
