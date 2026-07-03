# Project Refactor Request

We are still in active development. **Do not create new migration files or table-rename migrations.** Instead, **edit the existing migration files directly** so a fresh `php artisan migrate:fresh --seed` creates the new database structure.

## Goal

Refactor the subscription domain to follow a proper SaaS architecture with clear separation between Plans, Subscriptions, and Subscription Payments.

---

## 1. Rename the domain models

### Current → New

- `Pricing` → `Plan`
- `Plan` → `Subscription`
- `Subscription` → `SubscriptionPayment`

Update **everything** to use the new names consistently:

- Models
- Migrations
- Controllers
- Form Requests
- Policies
- Resources
- Services
- Repositories (if any)
- Factories
- Seeders
- Relationships
- Routes
- Validation rules
- Imports (`use` statements)
- Variable names
- Method names
- API responses
- Frontend components
- Frontend pages
- Frontend types/interfaces
- Frontend API calls
- Frontend labels
- Frontend forms
- Frontend navigation
- Frontend tables
- Frontend breadcrumbs
- Any remaining references to the old naming

The frontend and backend should use the exact same terminology.

---

## 2. Final database structure

### plans

This becomes the product catalog.

Example:

- Starter
- Business
- Enterprise

Move the existing pricing information here.

The table should contain things like:

- name
- description
- monthly_price (or existing price field)
- billing_cycle
- features
- status
- timestamps

### IMPORTANT

Update every plan description (including seeders) to mention:

> WhatsApp automated notifications

This feature should appear naturally as one of the included features in every plan description.

---

### subscriptions

Represents a business subscribing to a plan.

Fields should include:

- id
- business_id
- plan_id
- status
- starts_at
- ends_at
- trial_ends_at
- timestamps

Rename:

- start_date → starts_at
- end_date → ends_at

Relationships:

- belongsTo Business
- belongsTo Plan
- hasMany SubscriptionPayments

---

### subscription_payments

Represents every payment made for a subscription.

Fields:

- id
- subscription_id
- payment_method_id
- amount_paid
- transaction_id
- number_paid
- payment_status
- payment_proof
- verified_by
- verified_at
- rejection_reason
- notes
- timestamps

Use appropriate column lengths and decimal precision.

Relationships:

- belongsTo Subscription
- belongsTo PaymentMethod
- belongsTo User (verified_by)

---

## 3. Relationships

Plan

- hasMany Subscriptions

Subscription

- belongsTo Business
- belongsTo Plan
- hasMany SubscriptionPayments

SubscriptionPayment

- belongsTo Subscription
- belongsTo PaymentMethod
- belongsTo User (verified_by)

Business

- hasMany Subscriptions

---

## 4. Edit existing migrations only

Since this project is **still under development**:

- Do NOT generate migration files for renaming tables.
- Do NOT create data migration scripts.
- Do NOT create compatibility layers.
- Modify the existing migration files directly.
- Ensure a fresh migration produces the new schema correctly.

---

## 5. Update seeders

Update all seeders to match the new structure.

Rename seeded models accordingly.

Ensure plans include modern descriptions mentioning:

- WhatsApp automated notifications
- Dashboard access
- Reports
- Subscription management
- Any existing features

Seed data should feel realistic and production-ready.

---

## 6. Update backend

Refactor every backend reference to use the new architecture.

This includes:

- Models
- Controllers
- Requests
- Resources
- Relationships
- Validation
- Policies
- Factories
- Seeders
- Route model binding
- Queries
- Tests (if present)

Remove any obsolete naming.

---

## 7. Update frontend

Refactor the frontend to match the backend naming exactly.

Update:

- API endpoints
- Types
- Interfaces
- Components
- Forms
- Tables
- Pages
- Breadcrumbs
- Navigation
- Labels
- Imports
- Hooks/composables
- State management
- Validation
- Any displayed text

Users should only see:

- Plans
- Subscriptions
- Subscription Payments

The old terminology should no longer appear anywhere.

---

## 8. Laravel conventions

Follow Laravel best practices.

Use:

- `starts_at`
- `ends_at`
- proper relationship names
- proper model naming
- proper foreign keys
- proper eager loading where appropriate

Avoid unnecessary complexity.

---

## 9. Final verification

Before finishing, verify that:

- No old model names remain.
- No old table names remain.
- All foreign keys are valid.
- All relationships work.
- Seeders execute successfully.
- A fresh `php artisan migrate:fresh --seed` completes successfully.
- The frontend compiles successfully.
- The application behaves exactly the same functionally, but with the improved domain model and naming.

## Constraonts
- Do not hallucinate features
- Work as a senior systems engineer
- Consider scalability
