# POS Module Implementation Plan

## Architecture Overview
Dedicated POS module alongside existing Sales CRUD. Full-screen React interface with barcode scanning, instant search, cart management, checkout, hold/resume, and receipt printing.

## Backend (Laravel)

### 1. Database
- `held_carts` table: business_id, business_branch_id, user_id, customer_id (nullable), items (JSON), notes, timestamps

### 2. Models
- `HeldCart` extends BaseModel - fillable: business_branch_id, user_id, customer_id, items, notes; casts items to array/object

### 3. Form Requests
- `PosProductSearchRequest`: validate `q` (string, max:255)
- `PosCustomerSearchRequest`: validate `q` (string, max:255)
- `PosCheckoutRequest`: validate items, payments, customer_id, notes, discounts
- `PosHoldCartRequest`: validate items JSON, customer_id, notes
- `PosDeleteHeldCartRequest`: simple auth check

### 4. Resources
- `PosProductResource`: id, name, sku, barcode, price, stock, image, unit, tax
- `PosCustomerResource`: id, name, phone, customer_code, balance, loyalty_points

### 5. Service: PosService
- `searchProducts($query)`: fast search by barcode/SKU/name for user's branch; eager load category
- `searchCustomers($query)`: search by name/phone/customer_code; include user relationship
- `validateCart($items)`: verify all products exist, active, branch ownership, stock available
- `checkout($validated)`: DB transaction - create Sale, SaleItems, SalePayments, CashFlow, StockMovement, Receipt; handle split payments; deduct inventory
- `holdCart($items, $customerId, $notes)`: create HeldCart record
- `resumeCart($id)`: fetch HeldCart by id + user/branch scope
- `deleteHeldCart($id)`: delete with authorization

### 6. Controller: PosController
- searchProducts, searchCustomers, validateCart, checkout, holdCart, resumeCart, deleteHeldCart, getHeldCarts

### 7. Routes (routes/pos.php)
All `auth:sanctum`:
- GET  /pos/products/search  -> searchProducts
- GET  /pos/customers/search -> searchCustomers
- POST /pos/cart/validate     -> validateCart
- POST /pos/checkout          -> checkout
- POST /pos/cart/hold         -> holdCart
- GET  /pos/cart/held         -> getHeldCarts
- GET  /pos/cart/resume/{id}  -> resumeCart
- DELETE /pos/cart/held/{id}  -> deleteHeldCart

### 8. Tests (tests/Feature/POS/)
- PosSearchTest, PosCheckoutTest, PosValidationTest, PosHeldCartTest

## Frontend (React)

### 1. RTK Query Slice
`posQuery.ts` with baseUrl: `/pos`
- useLazySearchProductsQuery, useLazySearchCustomersQuery
- useValidateCartMutation, useCheckoutMutation
- useHoldCartMutation, useResumeCartQuery, useDeleteHeldCartMutation, useGetHeldCartsQuery

### 2. Page & Components (src/app/pages/dashboards/shared/pos/)
- `PosPage.tsx` - Main full-screen layout, keyboard shortcuts (F2, F4, F8, F9, ESC, Ctrl+Del)
- `PosLeftPanel.tsx` - Left side: search + product grid/table + cart
- `ProductSearch.tsx` - Search input with debounce (300ms), barcode scanner support (Enter key)
- `CartPanel.tsx` - Cart items list with quantity controls, discount, remove
- `CartSummary.tsx` - Subtotal, discount, tax, total
- `CustomerSelect.tsx` - Customer search modal, walk-in customer option
- `CheckoutModal.tsx` - Full checkout modal with payment form
- `PaymentForm.tsx` - Split payment support, amount received, change calculation
- `ReceiptPreview.tsx` - After-checkout receipt modal with print/PDF/new-sale options
- `HeldCartsPanel.tsx` - Held carts list with resume/delete actions

### 3. Route & Navigation
- Add to AdminRoutes: `<Route path='pos' element={<PosPage />} />`
- Add to ManagerRoutes: same
- Add POS link to AdminSidebar and ManagerSidebar

### 4. Design
- Full-screen layout (no sidebar visible, minimal chrome)
- CSS: max-height: 100vh, overflow hidden, use existing Tailwind/shadcn
- Large touch-friendly buttons for quantity +/- 
- Barcode scanner focus: auto-focus search input on mount

## File Creation Order
1. Migration + Model (HeldCart)
2. PosService
3. PosResource classes
4. PosFormRequest classes
5. PosController
6. Routes (pos.php + register in api.php)
7. posQuery.ts
8. POS page components
9. Route registration + sidebar links
10. Tests
