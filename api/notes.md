# ✅ Core Features (must-have)

1. Product management
   Add/edit/delete products
   SKU, barcode support
   Categories
   Stock quantity tracking
2. Stock tracking
   Stock in (purchases/restock)
   Stock out (sales/usage)
   Automatic quantity updates
3. Suppliers
   Track where products come from
   Useful for restocking & reports
4. Sales (or stock out)
   Record transactions
   Reduce inventory automatically
5. Purchase orders (stock in)
   Add stock into system
   Link to supplier
6. Reports
   Low stock alerts
   Sales reports
   Inventory valuation
7. Authentication
   Users (admin, staff)
   Permissions (optional but good)
   🧱 Recommended Database Tables
   🔹 Users
   users

- id
- name
- email
- password
- role (admin, staff)
- timestamps
  🔹 Categories
  categories
- id
- name
- timestamps
  🔹 Products
  products
- id
- name
- sku
- barcode
- category_id
- price
- cost_price
- quantity
- minimum_stock
- timestamps
  🔹 Suppliers
  suppliers
- id
- name
- phone
- email
- address
- timestamps
  🔹 Purchases (Stock In)
  purchases
- id
- supplier_id
- total_amount
- purchased_at
- timestamps
  🔹 Purchase Items
  purchase_items
- id
- purchase_id
- product_id
- quantity
- cost_price

👉 This lets one purchase have multiple products.

🔹 Sales (Stock Out)
sales

- id
- total_amount
- sold_at
- timestamps
  🔹 Sale Items
  sale_items
- id
- sale_id
- product_id
- quantity
- price
  🔹 Stock Movements (🔥 highly recommended)
  stock_movements
- id
- product_id
- type (in, out, adjustment)
- quantity
- reference_id (sale_id or purchase_id)
- timestamps

👉 This is powerful:

You get full history
You can debug stock issues later
Think of it like a ledger
🧩 Optional but smart additions
🔸 Warehouses (if scaling later)
warehouses

- id
- name
- location
  🔸 Inventory per warehouse
  inventory
- id
- product_id
- warehouse_id
- quantity
  🔸 Audit logs

Track who did what:

activity_logs

- id
- user_id
- action
- description
- timestamps
  ⚠️ Common mistakes (avoid these)
  ❌ Only storing quantity in products without history
  ❌ Not separating sale_items / purchase_items
  ❌ Hardcoding prices instead of storing per transaction
  ❌ No minimum_stock → no alerts

🧠 Simple mental model

Think like this:

Products = what you sell
Purchases = stock coming in
Sales = stock going out
Stock movements = the truth

## 🚀 To level it up

- Later you can add:

Barcode scanning
Invoice generation
Multi-currency
Dashboard analytics
API for mobile app

## To run at a go

php artisan make:controller CategoryController --api --model=Category --resource --requests
php artisan make:controller ProductController --api --model=Product --resource --requests
php artisan make:controller PurchaseController --api --model=Purchase --resource --requests
php artisan make:controller PurchaseItemController --api --model=PurchaseItem --resource --requests
php artisan make:controller RoleController --api --model=Role --resource --requests
php artisan make:controller SaleController --api --model=Category --resource --requests
php artisan make:controller SaleItemController --api --model=SaleItem --resource --requests
php artisan make:controller StockMovementController --api --model=StockMovement --resource --requests
php artisan make:controller SupplierController --api --model=Supplier --resource --requests

php artisan make:model PaymentStatus -a --api --controller=Settings/PaymentStatusController

php artisan make:service CategoryService
php artisan make:service ProductService
php artisan make:service PurchaseService
php artisan make:service PurchaseItemService
php artisan make:service RoleService
php artisan make:service SaleService
php artisan make:service SaleItemService
php artisan make:service StockMovementService
php artisan make:service SupplierService

## Self

php artisan make:controller --api --model=modelname --resource --requests

## To checkout

- purchase, purchaseItem
- Sale, SaleItem
