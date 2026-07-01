# Today's Tasks

## Task 1: Refactor Payment Statuses → Payment Methods

Rename the existing `payment_statuses` implementation to `payment_methods`.

This includes renaming:

* Database table (`payment_statuses` → `payment_methods`)
* Model
* Controller
* Requests/Resources
* Routes
* Relationships
* Imports
* References throughout the codebase

Ensure all existing functionality continues to work after the refactor.

---

## Task 2: Complete Pricing, Plans & Subscription Module

The base files (Model, Migration, Controller, and Resources) for **Pricing** already exist. Complete the implementation following the requirements below.

### Pricing

* Complete the CRUD implementation.
* Create a seeder with sensible default pricing suitable for the Ugandan market.
* Use realistic pricing tiers and modern SaaS conventions.

### Plans

A plan belongs to:

* `business`
* `pricing`

Required fields:

* `business_id`
* `pricing_id`

Add a status enum:

* `active`
* `inactive`
* `terminated`

Implement the necessary relationships, validation, and resources.

### Subscriptions

A subscription belongs to:

* `business`
* `plan`

Required fields:

* `business_id`
* `plan_id`

Subscriptions should also reference the payment method.

Use the newly renamed `payment_methods` table/model (formerly `payment_statuses`) and only allow enabled payment methods to be associated with subscriptions.

Implement all necessary relationships and validation.

---

# Frontend

## Public Website

* Display the available pricing plans on the Home page.
* Do **not** place pricing inside any dashboard.

## Admin Dashboard

### Subscriptions Page

Create a dedicated Subscriptions route/page that includes:

* A table listing subscriptions.
* A Shadcn Badge at the top indicating the currently active plan.
* Appropriate Lucide icons where applicable.

### Dashboard Overview

Inside the existing `OverviewCards` component, add cards displaying:

* Current subscription
* Current plan

Reuse the existing design system and component patterns.

---

# Seeder Requirements

Seed realistic SaaS pricing tailored to the Ugandan economy.

Include multiple pricing tiers (for example: Starter, Growth, and Enterprise) with sensible monthly pricing and features.

Avoid hardcoding assumptions that would make future expansion difficult.

---

# Engineering Constraints

* Do not hallucinate features or requirements.
* Work as a senior software engineer.
* Make modern, scalable architectural decisions.
* Keep the implementation maintainable and extensible.
* Add meaningful comments where they improve readability.
* Reuse existing frontend patterns.
* Use the existing Shadcn UI components.
* Use Lucide icons already used throughout the project.
* Do not reinvent existing abstractions.
* Keep naming consistent across backend and frontend.
* Preserve backward compatibility where possible during the refactor.
* Follow existing project conventions and coding style.
* Prioritize scalability, readability, and maintainability over shortcuts.
