# Today's Task

Implement a production-ready Point of Sale (POS) module in the existing Laravel + React inventory management system.

The implementation must follow the current architecture, coding conventions, API structure, authorization policies, business scoping, branch scoping, UI components, and design system already used throughout the project.

The POS must be optimized for speed and daily cashier operations, not administrative data entry.

---

# OBJECTIVES

Build a dedicated POS workflow separate from the existing Sales CRUD.

The POS should allow a cashier/admins/sellers to:

* Scan a barcode
* Search products instantly
* Add products to cart (sale)
* Edit quantity
* Apply discounts
* Select customer
* Accept payment
* Complete sale
* Print receipt

The experience should require minimal clicks.

---

# BACKEND

## Create a dedicated POS module

Do not overload the existing Sales controller.

Create:

* PosController
* PosService
* PosRequests
* PosResources

Reuse existing Sale, SaleItem, Payment, Inventory and CashFlow logic wherever possible.

---

## Product Search API

Support searching by:

* Barcode
* SKU
* Product Name

Results should include:

* Product
* SKU
* Barcode
* Current selling price
* Available stock
* Product image (if available)
* Unit
* Tax
* Discount (if applicable)

Searching must be extremely fast.

---

## Customer Search API

Support:

* Name
* Phone
* Customer Number

Include:

* Outstanding balance
* Loyalty points (if supported)
* Default customer

Support Walk-in Customer.

---

## Cart Validation

Before checkout verify:

* Product exists
* Product active
* Branch stock available
* Quantity available
* Selling price valid
* Business ownership
* Branch ownership

Prevent overselling.

---

## Checkout

Checkout should:

Create:

* Sale
* Sale Items
* Payments
* CashFlow entries
* Inventory deductions
* Stock movements

Generate invoice number.

Wrap the entire checkout inside a database transaction.

Rollback on failure.

---

## Hold Sale

Cashier should be able to:

Hold Cart

Resume Cart

Delete Held Cart

Held carts should not affect inventory.

---

## Payments

Support:

* Cash
* Card
* Mobile Money
* Bank
* Mixed Payment

Mixed payment example:

Cash 50,000

Mobile Money 20,000

Balance automatically calculated.

---

## Receipt

Generate printable receipt including:

Business

Branch

Cashier

Invoice Number

Customer

Items

Discounts

Taxes

Payments

Change Returned

QR Code (optional)

Printer integration should reuse the existing Printer model.

---

## Security

Respect:

Business scoping

Branch scoping

User permissions

Cashier permissions

Audit logging

Soft deletes

Authorization policies

---

# FRONTEND

Create a dedicated page:

/pos

This should be a full-screen interface.

Do NOT reuse the Sales CRUD interface.

---

## Layout

Left Side

* Barcode input (always focused)
* Product search
* Product grid/list
* Category quick filters (if categories exist)

Right Side

Shopping cart

Each row:

* Product
* Qty
* Price
* Discount
* Remove

Bottom section:

Subtotal

Discount

Tax

Grand Total

Payment button

Hold button

Clear Cart

---

## Checkout Modal

Allow:

Customer selection

Walk-in customer

Payment methods

Split payments

Amount received

Change calculation

Notes

Confirm Sale

---

## UX Requirements

Barcode scanner should work by simply scanning.

Pressing Enter after scanning should immediately add the product.

Keyboard shortcuts:

F2 → Search

F4 → Customer

F8 → Hold Sale

F9 → Checkout

ESC → Cancel Dialog

Ctrl + Delete → Clear Cart

The interface should be usable without touching the mouse.

---

## Receipt

After successful checkout:

Show receipt preview.

Allow:

Print

Download PDF

New Sale

Reprint previous receipt

---

## Error Handling

Display friendly messages for:

Out of stock

Insufficient quantity

Invalid barcode

Payment mismatch

Permission denied

Network failure

Rollback errors

---

# PERFORMANCE

Optimize for speed.

Use debounced search.

Minimize API requests.

Cache product lookups where appropriate.

Avoid N+1 queries.

Eager load required relationships.

---

# TESTING

Implement:

Feature tests

Unit tests

API tests

Checkout tests

Stock validation tests

Payment tests

Held cart tests

Permission tests

---

# DELIVERABLES

At completion provide:

1. Database changes
2. API endpoints created
3. React pages added
4. Components added
5. Services created
6. Files modified
7. Test coverage summary
8. Future enhancements that integrate cleanly with Returns, Receipts, Loyalty, and Gift Cards without requiring breaking changes.
