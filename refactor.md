# Plan: Add a Receipt System

## Goal

Implement a complete receipt system so that every completed sale generates a receipt that can be viewed, downloaded as a PDF, and stored for future reference.

The project uses:

- Laravel (Backend/API)
- React SPA (Frontend)
- shadcn/ui
- Lucide React

The implementation should follow the existing project architecture and coding conventions.

---

# Backend

## Models & Migrations

Create the following models and migrations.

### receipts

Fields:

- id
- receipt_number (unique)
- customer_id (nullable, foreign key)
- user_id (cashier, foreign key)
- subtotal
- discount (default 0)
- tax (default 0)
- total
- amount_paid
- change_given
- payment_method
- status (`completed`, `refunded`, `voided`)
- notes (nullable)
- timestamps

### receipt_items

Fields:

- id
- receipt_id (foreign key)
- product_id (foreign key)
- product_name (snapshot)
- sku (nullable snapshot)
- quantity
- unit_price (price at the time of sale)
- discount (default 0)
- line_total
- timestamps

---

## Relationships

### Receipt

- belongsTo(User::class, 'user_id')
- belongsTo(Customer::class)
- hasMany(ReceiptItem::class)

### ReceiptItem

- belongsTo(Receipt::class)
- belongsTo(Product::class)

---

## Receipt Creation

Whenever a sale is successfully completed:

1. Create a Receipt.
2. Generate a unique receipt number.
3. Save the cashier.
4. Save the customer (if one exists).
5. Save:
   - subtotal
   - tax
   - discount
   - total
   - amount paid
   - change given
   - payment method

6. Create ReceiptItem records for every purchased product.
7. Store product snapshots (name, SKU, unit price) so historical receipts remain accurate even if products are edited later.

Receipt creation must happen inside the same database transaction as the sale so the entire checkout either succeeds or rolls back.

---

# API

Create REST endpoints for receipts.

Examples:

- GET /receipts
- GET /receipts/{receipt}
- GET /receipts/{receipt}/pdf

The JSON endpoint should return:

- receipt information
- cashier
- customer
- receipt items
- totals
- payment details

Use eager loading to avoid N+1 queries.

---

# PDF Generation

The frontend is a React SPA.

Do **not** generate PDFs in React.

Generate PDFs on the Laravel backend using a package such as:

- barryvdh/laravel-dompdf

Create a dedicated Blade template for the receipt PDF.

The PDF should include:

- Store logo/name
- Receipt number
- Date & time
- Cashier
- Customer (if available)
- Products purchased
- Quantity
- Unit price
- Discounts
- Taxes
- Totals
- Amount paid
- Change given
- Payment method
- Thank-you message

The PDF should:

- look like a professional POS receipt/invoice
- be printer-friendly
- support A4
- have clean spacing and typography

---

# Frontend (React)

## Receipts List

Create a Receipts page.

Features:

- Search
- Filter by customer
- Filter by cashier
- Filter by payment method
- Filter by status
- Filter by date
- Pagination

Columns:

- Receipt Number
- Customer
- Cashier
- Total
- Payment Method
- Status
- Created At
- Actions

Actions:

- View
- Download PDF
- Open PDF

---

## Receipt Details Page

Create a detailed receipt page.

Display:

- Receipt number
- Date & time
- Cashier
- Customer
- Payment method
- Status badge

Products table:

- Product
- Quantity
- Unit Price
- Discount
- Line Total

Totals section:

- Subtotal
- Discount
- Tax
- Grand Total
- Amount Paid
- Change Given

---

## UI

Use **shadcn/ui** components wherever possible.

Suggested components:

- Card
- Table
- Badge
- Separator
- Button
- DropdownMenu
- ScrollArea
- Skeleton
- Alert
- Tooltip

Use **Lucide React** icons consistently.

Suggested icons:

- Receipt
- ShoppingCart
- User
- Users
- CreditCard
- Calendar
- Printer
- Download
- FileText
- Eye
- ArrowLeft
- Search
- Filter

Support:

- Dark mode
- Responsive layouts
- Desktop
- Tablet

Match the existing design language of the application.

---

# PDF Actions

On the Receipt Details page include:

- Download PDF
- Open PDF in a new tab

The React frontend should simply call the backend PDF endpoint.

---

# Code Quality

- Follow Laravel best practices.
- Keep controllers thin.
- Use Eloquent relationships.
- Use Form Requests where appropriate.
- Reuse existing services/actions if the project already follows that pattern.
- Use proper validation.
- Use eager loading.
- Keep code clean, typed, and maintainable.
- Follow the existing folder structure and coding style.

---

# Important

- Do not break the existing checkout flow.
- Integrate receipt generation seamlessly into completed sales.
- Ensure receipts remain historically accurate by storing product snapshots.
- Make the feature production-ready with clean architecture, polished UI, and maintainable code.
