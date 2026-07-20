# NEW MODULE

Follow the existing project architecture and conventions to implement a complete **Audit** module.

Do **not** introduce a new architecture or coding style. Analyze the current Laravel API and React (TypeScript) SPA and implement this module exactly as the rest of the project is structured.

The implementation should reuse existing services, patterns, middleware, permissions, layouts, components, API conventions, validation, response format, and UI design.

---

# Why add Audit Module

The application already maintains the current product quantity by updating `products.quantity` whenever stock movements occur.

However, businesses also need the ability to:

- Perform physical stock counts.
- Compare physical quantities against system quantities.
- Adjust inventory after an audit.
- Keep a permanent audit trail.
- Audit financial records and cash balances.
- View historical audit reports.

The Audit module should integrate naturally with the existing Inventory and Financial modules.

---

# Scope

Implement:

- Product Audits
- Financial Audits

The implementation should include:

- Backend
- Frontend
- Routes
- Permissions
- Navigation
- API integration
- UI
- Validation
- Reports where applicable

---

# Product Audit

Implement complete product stock auditing.

## Backend

Create:

- ProductAudit
- ProductAuditItem

Suggested fields include:

ProductAudit

- business_id
- business_branch_id
- audit_number
- audit_date (follow a systematic audit number eg "FAUDIT+ business branch audit length for financial autit and PAUDIT + "same as financial audit" for product audit")
- status (enum)
- notes
- performed_by
- approved_by
- timestamps

ProductAuditItem

- product_id
- system_quantity
- counted_quantity
- difference
- adjustment_quantity
- notes

---

## Workflow

When creating an audit:

- Fetch the current quantity from `products.quantity`.
- Store it as `system_quantity`.
- Allow the user to enter the physical quantity.
- Automatically compute the difference.
- Allow saving as Draft.

Statuses should follow the existing project conventions.

Suggested statuses:

- Draft
- In Progress
- Completed
- Approved
- Cancelled

Only Approved audits should affect inventory.

---

## Approval

When an audit is approved:

For every item whose counted quantity differs from the system quantity:

1. Create an Inventory Movement using the existing inventory movement infrastructure (to the audited business branch).
2. Update the product quantity using the project's existing inventory service/pattern.
3. Record the adjustment reason as Stock Audit.

Do not bypass the current inventory movement workflow.

The inventory movement should become the historical record.

---

# Financial Audit

Implement complete financial audits.

Financial audits are intended to verify recorded financial balances against actual balances.

Create:

- FinancialAudit

Fields may include:

- business_id
- business_branch_id
- audit_number
- audit_date
- expected_balance
- actual_balance
- difference
- notes
- status
- performed_by
- approved_by

The audit should compare the expected financial balance with the actual counted balance.

If there is a variance:

- Store the difference.
- Preserve it as part of the audit history.
- Do not automatically modify financial records unless that matches the existing project architecture.

If the project already contains Financial Transactions, integrate with them.

---

# Integration

Integrate naturally with:

- Products
- Inventory Movements
- Financial Transactions
- Dashboard statistics
- Reports where appropriate

Do not duplicate business logic.

Reuse existing services whenever possible.

---

# Frontend

Follow the current React application architecture.

Create all necessary:

- pages
- components
- API hooks/services
- types
- forms
- dialogs
- tables
- filters
- breadcrumbs
- routes

Reuse existing reusable components.

---

# UI

Follow the existing application design.

Reuse existing:

- shadcn/ui components
- lucide-react icons
- table components
- dialogs
- sheets
- cards
- badges
- pagination
- search
- filters
- loading states
- empty states
- confirmation dialogs

Do not introduce a different design language.

The module should look identical to the rest of the application.

---

# Pages

Implement pages similar to the rest of the system:

Product Audits

- Audit List
- Create Audit
- View Audit
- Edit Draft Audit
- Approve Audit
- Print/View Audit Report

Financial Audits

- Audit List
- Create Audit
- View Audit
- Edit Draft Audit
- Approve Audit
- Print/View Audit Report

---

# API

Follow existing API conventions.

Implement:

- index
- show
- store
- update
- destroy (if allowed by current project conventions)
- approve
- cancel

Use:

- Form Requests
- API Resources (if used)
- Policies / Authorization
- Validation
- Database Transactions where appropriate

Return responses using the existing API response structure.

---

# Navigation

Add menu entries following the current sidebar structure.

Suggested placement:

Inventory

- Product Audits

Finance

- Financial Audits

Only if these sections already exist.

Otherwise, place them where they best match the current navigation.

---

# Authorization

This module is **Admin only**.

Only administrators should be able to:

- View audits
- Create audits
- Edit drafts
- Approve audits
- Cancel audits
- Delete audits (if supported)

Non-admin users should have no access.

Use the project's existing authorization approach.

---

# Reports

Implement printable audit reports following the project's existing reporting style.

Product Audit Report should include:

- Audit details
- Products
- System Quantity
- Counted Quantity
- Difference
- Adjustments
- Auditor
- Approval information

Financial Audit Report should include:

- Expected balance
- Actual balance
- Difference
- Notes
- Auditor
- Approval information

---

# Constraints

- Maintain the existing architecture.
- Reuse existing project services and patterns.
- Do not duplicate inventory or financial logic.
- Follow the project's coding conventions.
- Use database transactions where needed.
- Keep business logic inside the appropriate services.
- Reuse existing repositories/services/helpers if they already exist.
- Follow RESTful API conventions.
- Follow existing route organization.
- Wire up the complete frontend and backend.
- Reuse existing shadcn/ui components and lucide-react icons.
- Ensure responsive layouts.
- Preserve existing UI/UX consistency.
- Ensure the implementation is production-ready.
- If an existing package, helper, component, or service already solves a problem, use it instead of creating a new implementation.
