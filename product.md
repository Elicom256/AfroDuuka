You are working on my Laravel backend for **DuukaFlow**, a multi-tenant inventory management SaaS.

I have refactored the Product domain and need you to update the existing implementation so that the database, model, controllers, services, validation, resources, tests, and frontend-facing API responses are consistent with the new Product pricing/tax structure.

### IMPORTANT RULES

- Inspect the existing codebase before making changes.
- Follow the project's existing architecture and conventions.
- Do NOT rewrite unrelated functionality.
- Do NOT introduce unnecessary packages.
- Do NOT create duplicate models, services, migrations, or controllers.
- Do NOT remove existing functionality unless it conflicts with the changes below.
- Update all affected code paths, not just the Product model.
- Search the entire codebase for old Product pricing fields before finishing.
- If the existing database uses `price`, determine all places that depend on it and edit them consistently to `selling_price`.
- Keep the implementation compatible with the existing business/branch multi-tenant structure.

---

## NEW PRODUCT PRICING/TAX STRUCTURE

The Product should use:

```text
business_branch_id
product_category_id
tax_category_id

name
sku
barcode

quantity

cost_price
selling_price
is_tax_inclusive

reorder_level
description
emoji
status
last_sold_at
expiry_date
```

### Remove

The old:

```text
price
markup_percentage
```

`markup_percentage` should NOT be stored in the database.

Markup is derived information:

```text
markup_percentage =
((selling_price - cost_price) / cost_price) * 100
```

If cost price is zero/null, return null rather than causing a division-by-zero error.

The backend may expose calculated markup in API responses if the existing frontend benefits from it, but it must NOT be persisted.

---

# DATABASE CHANGES

Just edit migrations directly, so ne new migrations, don't mind I'm in development

The final Product database structure should support:

```text
cost_price
selling_price
is_tax_inclusive
tax_category_id
```

Requirements:

- `cost_price`: decimal, appropriate precision/scale for monetary values.
- `selling_price`: decimal, appropriate precision/scale for monetary values.
- `is_tax_inclusive`: boolean.
- `tax_category_id`: nullable foreign key referencing `tax_categories.id`.
- Remove/rename the old `price` column according to the existing database state.
- Remove `markup_percentage` if it exists.

Do NOT blindly drop existing production Logic.

---

# PRODUCT MODEL

Update `Product.php`.

The fillable fields should reflect the new structure.

The model should include:

```php
public function taxCategory(): BelongsTo
{
    return $this->belongsTo(TaxCategory::class);
}
```

Use appropriate casts:

```php
'is_tax_inclusive' => 'boolean',
'cost_price' => 'decimal:2',
'selling_price' => 'decimal:2',
```

Keep the existing relationships and functionality.

Expose a calculated markup percentage if appropriate, but do not store it.

---

# CONTROLLERS

Inspect all Product-related controllers.

Update:

- validation
- create/store logic
- update logic
- show logic
- index/list logic
- API resources
- filtering/sorting if affected

Replace old references such as:

```php
price
```

with:

```php
selling_price
```

where the code refers to Product selling price.

Add validation for:

```text
tax_category_id
is_tax_inclusive
cost_price
selling_price
```

`tax_category_id` should be nullable.

The tax category must belong to the appropriate business/tenant context before allowing it to be assigned to a Product.

Do NOT allow a user from one business/branch to attach another business's tax category to their Product.

---

# SERVICES

Search the entire backend for Product business logic.

Update all Product-related services, including but not limited to:

- Product creation
- Product updates
- Product imports
- Stock receiving
- Purchasing
- Sales
- Inventory valuation
- Search/filter services
- Reports
- Dashboard calculations

Any existing logic using:

```text
product.price
```

must be reviewed and changed appropriately.

Where the application means the selling price, use:

```text
product.selling_price
```

Where it means acquisition/purchase cost, use:

```text
product.cost_price
```

Do NOT blindly replace every occurrence of `price`; inspect the context first.

---

# SALES / PURCHASE IMPACT

Inspect SaleItem, PurchaseItem, Order, Invoice, and related services.

Determine which existing fields represent:

- product selling price
- product acquisition cost
- transaction unit price

Preserve the distinction between Product's configured prices and transaction-time prices.

Do NOT make historical transactions depend on the Product's current selling price.

If SaleItem already stores a unit price, preserve that behavior.

The Product's `selling_price` should be used as the default when creating a new sale item where appropriate.

---

# API RESPONSES

Update API Resources / Transformers / DTOs so the frontend receives:

```text
cost_price
selling_price
is_tax_inclusive
tax_category_id
```

and, if useful:

```text
markup_percentage
```

as a calculated/read-only value.

Do not expose database fields that no longer exist.

---

# TESTS

Update existing Product tests and add tests where necessary for:

1. Product creation with cost/selling price.
2. Product creation with a tax category.
3. Product creation without a tax category.
4. `is_tax_inclusive` being persisted correctly.
5. Product update.
6. Selling price being returned correctly.
7. Markup being calculated correctly.
8. Zero/null cost price not causing division by zero.
9. A Product cannot reference a TaxCategory belonging to another business/tenant.
10. Existing Product functionality remains intact.

---

# FINAL CODEBASE AUDIT

Before finishing, search the repository for:

```text
product->price
product.price
'price'
"price"
markup_percentage
```

Review every relevant occurrence.

Do NOT blindly rename unrelated `price` fields belonging to:

- SaleItem
- PurchaseItem
- invoices
- payments
- other domains

Only change fields that refer specifically to the Product model's old selling price.

Also check:

- migrations
- factories
- seeders
- form requests
- controllers
- services
- resources
- policies
- tests
- frontend API assumptions if present in this repository

Finally, run the relevant Laravel tests and static/code checks available in the project.

Report:

1. Files changed.
2. Database changes.
3. Old Product fields removed/replaced.
4. New Product fields added.
5. Any places where the old `price` field was intentionally preserved because it belongs to another domain.
6. Tests/checks executed and their results.

Do not modify unrelated application functionality.
