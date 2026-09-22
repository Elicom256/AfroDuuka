You are working on my Laravel backend for **DuukaFlow**, a multi-tenant inventory management SaaS.

I am implementing a flexible Tax Management module.

I have ALREADY created these models, migrations, controllers, factories/resources where applicable:

```bash
php artisan make:model TaxCategory -m --api
php artisan make:model TaxRate -m --api
php artisan make:model TaxPayment -m --api
```

Use the existing generated files. Do NOT generate duplicate Tax models or duplicate migrations/controllers.

Before implementing anything, inspect the existing project architecture and follow its established conventions.

---

# IMPORTANT DOMAIN CONCEPT

DuukaFlow must NOT attempt to determine or enforce every legal tax obligation for a business.

The system should allow a business to:

1. Define tax categories.
2. Define tax rates.
3. Assign tax categories to products.
4. Configure whether product prices are tax-inclusive.
5. Calculate transaction taxes where applicable.
6. Record actual tax payments made by the business.
7. View tax payment analytics.

The system should support businesses that have different tax obligations.

Examples may include:

```text
VAT
Corporate Income Tax
PAYE
Rental Tax
Other Tax
```

Do not hard-code these tax types into the application.

Businesses should be able to create and manage their own tax categories.

---

# DATA MODEL

## TAX CATEGORIES

A TaxCategory represents a business-defined tax classification.

At minimum:

```text
id
business_branch_id
name
description
is_active
created_at
updated_at
```

Examples:

```text
VAT
Corporate Income Tax
PAYE
Rental Tax
Other
```

A TaxCategory belongs to a BusinessBranch.

A BusinessBranch has many TaxCategories.

Tax categories must be isolated by business/tenant.

A user must NOT be able to access, update, delete, or assign another business/branch's TaxCategory.

---

# TAX RATES

A TaxRate represents a percentage rate associated with a TaxCategory.

At minimum, support:

```text
id
tax_category_id
name
rate
jurisdiction_zone
is_active
created_at
updated_at
```

Example:

```text
name: VAT Standard
rate: 0.18
jurisdiction_zone: Uganda
```

IMPORTANT:

Use a decimal representation consistently.

If the application stores:

```text
0.18
```

that represents:

```text
18%
```

Do NOT mix representations such as `18` and `0.18`.

Use an appropriate decimal database type.

A TaxRate belongs to a TaxCategory.

A TaxCategory can have many TaxRates.

Do not hard-code a specific country or tax percentage into the system.

---

# TAX PAYMENT

A TaxPayment represents an ACTUAL tax payment made by the business.

This is NOT the same thing as tax calculated on a sale or purchase.

At minimum:

```text
id
business_branch_id
tax_category_id
amount
payment_date
tax_period_start
tax_period_end
reference
notes
created_at
updated_at
```

Relationships:

```text
BusinessBranch
    hasMany TaxPayments

TaxCategory
    hasMany TaxPayments

TaxPayment
    belongsTo BusinessBranch

TaxPayment
    belongsTo TaxCategory
```

`tax_category_id` should normally be required because every recorded payment should identify what type of tax was paid.

However, inspect the existing application's conventions before finalizing nullability.

---

# IMPORTANT DISTINCTION

Do NOT use `tax_payments` to store tax calculated on individual sales.

For example:

```text
Sale
Subtotal: 100,000
Tax: 18,000
Total: 118,000
```

That transaction tax belongs to the sale/sale-item domain.

`tax_payments` means:

```text
Business actually paid UGX 2,000,000 in VAT
```

These are separate concepts.

---

# PRODUCT INTEGRATION

The Product model now contains:

```text
tax_category_id
is_tax_inclusive
cost_price
selling_price
```

Integrate the Tax module with Product.

A Product may have:

```text
tax_category_id = null
```

because tax configuration may not apply or may not have been specified.

When assigning a tax category:

- Verify the tax category belongs to the same business/branch context.
- Do not allow cross-tenant tax category assignment.

---

# TAX CATEGORY CRUD

Implement complete CRUD.

Required operations:

```text
GET    /tax-categories
GET    /tax-categories/{id}
POST   /tax-categories
PUT    /tax-categories/{id}
DELETE /tax-categories/{id}
```

Use the project's existing route/controller conventions instead of blindly using these exact paths if the application has a different established API structure.

Support:

- create
- update
- delete
- activate/deactivate
- list
- retrieve individual category

Do not allow deletion if doing so would break existing historical records unless the project's existing deletion strategy supports safe deletion.

Consider whether soft deletion or deactivation is more appropriate.

Historical tax records should remain valid.

---

# TAX RATE CRUD

Implement CRUD for TaxRates.

Required functionality:

- create rate
- update rate
- list rates
- retrieve rate
- delete/deactivate rate

Validate:

```text
name        required
rate        required
jurisdiction_zone nullable/string
is_active   boolean
tax_category_id valid
```

Ensure the TaxRate's TaxCategory belongs to the authenticated user's business/branch.

---

# TAX PAYMENT CRUD

Implement:

- create tax payment
- list tax payments
- retrieve tax payment
- update tax payment
- delete tax payment if appropriate

Validate:

```text
tax_category_id
amount
payment_date
tax_period_start
tax_period_end
reference
notes
```

Amount must be positive.

Payment date must be a valid date.

Tax period dates should be valid and logically ordered.

A payment must belong to the authenticated user's BusinessBranch.

Do NOT accept arbitrary `business_branch_id` from the client if the authenticated user already determines the branch context.

Derive ownership from the authenticated user/business context where the existing application architecture supports this.

---

# TAX PAYMENT ANALYTICS

Add backend support for useful analytics.

At minimum:

### Total tax paid

```text
Total tax payments
```

### Tax paid by category

Example:

```text
VAT:                    12,400,000
Corporate Income Tax:    8,000,000
PAYE:                    4,200,000
Rental Tax:                200,000
```

### Tax paid by period

Support filtering by:

```text
today
this week
this month
this quarter
this year
custom date range
```

### Tax payment history

Return records suitable for rendering in a frontend table.

Where practical, use database aggregation rather than loading every payment into PHP and calculating everything manually.

---

# TAX SETTINGS

Implement backend support for tax configuration settings.

The frontend should eventually be able to:

```text
View tax categories
Create tax category
Edit tax category
Deactivate tax category

View tax rates
Create tax rate
Edit tax rate
Deactivate tax rate
```

Do not create a separate generic "tax settings" table unless the existing application architecture genuinely requires one.

Tax categories and rates are themselves the configuration.

---

# API RESOURCES

Use the project's existing API Resource conventions.

TaxCategory responses should include useful information such as:

```text
id
name
description
is_active
rates
```

TaxRate responses:

```text
id
name
rate
jurisdiction_zone
is_active
tax_category_id
```

TaxPayment responses:

```text
id
amount
payment_date
tax_period_start
tax_period_end
reference
notes
tax_category
```

Avoid unnecessary nested data that creates N+1 queries.

Use appropriate eager loading.

---

# AUTHORIZATION / TENANT ISOLATION

This is CRITICAL.

Every operation must respect the existing DuukaFlow business/branch ownership model.

A user belonging to Branch A must not be able to:

- retrieve Branch B's tax categories
- retrieve Branch B's tax rates
- create payments against Branch B
- update Branch B's tax categories
- assign Branch B's tax category to Branch A products

Inspect the existing authorization/policy/service patterns and integrate with them rather than inventing a second authorization system.

---

# DATABASE CONSTRAINTS & INDEXES

Add appropriate foreign keys and indexes.

At minimum consider indexes on:

```text
tax_categories.business_branch_id

tax_rates.tax_category_id

tax_payments.business_branch_id
tax_payments.tax_category_id
tax_payments.payment_date
```

Use the existing project's migration conventions.

---

# FACTORIES / SEEDERS

Update or create factories if the project uses factories for testing.

Do not add fake tax data to production seeders unless the project already uses seeded default data for every business.

---

# TESTS

Add/update tests covering:

### TaxCategory

1. Create category.
2. List categories.
3. Update category.
4. Deactivate category.
5. User cannot access another branch's category.

### TaxRate

1. Create rate.
2. Update rate.
3. List rates.
4. Validate decimal rate.
5. Ensure rate belongs to an accessible TaxCategory.
6. Prevent cross-tenant access.

### TaxPayment

1. Record payment.
2. List payments.
3. Update payment.
4. Validate positive amount.
5. Filter by date range.
6. Filter by tax category.
7. Aggregate total payments.
8. Aggregate payments by tax category.
9. Prevent cross-tenant access.

### Product integration

10. Product can reference a TaxCategory.
11. Product can have no TaxCategory.
12. Product cannot reference another branch's TaxCategory.

---

# FRONTEND CONTRACT

Inspect the existing frontend/API consumption patterns and make sure the API responses provide everything needed to implement:

```text
Tax Settings
├── Tax Categories
│   ├── List
│   ├── Create
│   ├── Edit
│   └── Deactivate
│
├── Tax Rates
│   ├── List
│   ├── Create
│   ├── Edit
│   └── Deactivate
│
└── Tax Payments
    ├── List
    ├── Record Payment
    ├── Edit
    └── Analytics
```

Do NOT implement frontend UI unless the frontend is part of the task/repository being modified. If it is present, follow its existing architecture and conventions.

---

# FINAL AUDIT

Before finishing:

Search the repository for:

```text
TaxCategory
TaxRate
TaxPayment
tax_category_id
tax_rate
tax_payment
```

Check all affected:

- Models
- Relationships
- Migrations
- Controllers
- Form Requests
- Services
- API Resources
- Policies
- Routes
- Factories
- Tests
- Product integration
- Sales/purchase integration where applicable

Run the relevant migrations/tests.

Do NOT modify unrelated modules.

Finally report:

1. Files created/modified.
2. Database schema changes.
3. TaxCategory functionality.
4. TaxRate functionality.
5. TaxPayment functionality.
6. Analytics endpoints/queries.
7. Authorization/tenant-isolation approach.
8. Product integration.
9. Tests executed and their results.
10. Any design decisions or assumptions that require my approval.
