# AfroDuuka — Dashboard, Subscription & RBAC Refactor

You are working on an existing Laravel API + React TypeScript SPA inventory SaaS called **AfroDuuka**.

This is a refactor of the existing project, NOT a greenfield rewrite.

The goal is to restructure the application around a clean **subscription → dashboard → RBAC → permissions** architecture while preserving all currently working business functionality.

---

# 1. FIRST: INSPECT THE EXISTING PROJECT

Before modifying anything:

1. Inspect the complete Laravel backend structure.
2. Inspect the React frontend structure.
3. Identify:

   * authentication
   * users
   * roles
   * permissions
   * businesses/tenants
   * branches
   * products
   * inventory
   * sales
   * purchases
   * suppliers
   * customers
   * expenses
   * cash flow
   * reports
   * subscriptions/plans
   * existing dashboards
   * middleware/policies/gates
   * API routes
   * frontend routing
   * navigation/sidebar
   * existing dashboard components
4. Determine what already exists before creating new tables, models, services, routes or components.
5. Do NOT duplicate existing functionality.
6. Do NOT delete working business logic unless it is being deliberately replaced.
7. Preserve existing database relationships and tenant isolation.

Create a short internal implementation plan based on the actual project structure before making the changes.

---

# 2. CORE ARCHITECTURAL PRINCIPLE

The application must separate these concepts:

## Subscription Plan

Determines which dashboards/capabilities a BUSINESS is entitled to.

## Dashboard

Determines the workspace/context available to the user.

## Role

Determines what type of user they are.

## Permission

Determines what actions that user can perform.

Do NOT treat dashboards and roles as the same thing.

The architecture should conceptually be:

Subscription Plan
→ Dashboard Entitlements
→ User Role
→ Permissions
→ Actions

---

# 3. SUBSCRIPTION TIERS

Refactor the subscription architecture around these three tiers.

## STARTER

Starter has exactly:

### 1 dashboard

**Management**

Starter is intended for small businesses where the owner manages the entire business from one unified workspace.

The Management dashboard on Starter must provide access to all core business operations available to the Starter plan.

It must NOT force the owner to switch between Operations or Procurement dashboards.

Starter Management should cover:

* Overview
* Sales
* Purchases
* Inventory
* Products
* Customers
* Suppliers
* Expenses
* Cash Flow
* Reports
* Business settings
* User management
* Subscription/settings where applicable

The owner should be able to perform the complete Starter workflow from this single Management workspace.

---

# 4. PREMIUM

Premium has exactly:

### 3 dashboards

1. Management
2. Operations
3. Procurement

## Management

Purpose:

> Business owner / manager visibility and control.

Management should provide:

* Business overview
* Revenue
* Sales performance
* Profit/Loss
* Inventory value
* Low stock
* Out of stock
* Top products
* Slow-moving products
* Purchases
* Cash flow
* Expenses
* Reports
* Branch performance where applicable
* Business-level controls

Management should provide broad visibility while respecting the user's actual permissions.

---

## Operations

Purpose:

> Day-to-day business execution.

Operations should contain functionality such as:

* Sales
* Customers
* Product lookup
* Inventory lookup
* Purchases where permitted
* Receiving stock where permitted
* Returns
* Stock movements
* Stock adjustments where permitted
* Expenses where permitted
* Daily operational summaries

Operations should focus on executing transactions rather than exposing sensitive management controls.

---

## Procurement

Purpose:

> Inventory replenishment and purchasing.

Procurement should contain:

* Procurement overview
* Reorder suggestions
* Sales velocity
* Low-stock analysis
* Stock coverage/days remaining
* Suppliers
* Purchase orders
* Purchase history
* Receiving
* Procurement history
* Suggested purchase quantities

---

# 5. ENTERPRISE

Enterprise retains the same three core dashboards:

1. Management
2. Operations
3. Procurement

Do NOT create unnecessary dashboard proliferation.

Enterprise instead unlocks additional capabilities such as:

* Multi-branch management
* Advanced RBAC
* Custom roles
* Advanced reporting
* Branch-level permissions
* Advanced audit logs
* Advanced procurement
* Advanced financial controls
* API/integrations
* Enterprise automation

The three core dashboards remain the primary navigation model.

---

# 6. RBAC — REFACTOR THIS CAREFULLY

Implement proper role-based access control.

The system must NOT simply say:

```text
user.dashboard = management
```

Instead:

```text
Business subscription
        ↓
Available dashboards
        ↓
User role
        ↓
Permissions
        ↓
Allowed actions
```

A user may have access to a dashboard but still be restricted from specific actions.

---

# 7. PERMISSION MODEL

Use granular permissions.

Do not create overly broad permissions such as:

```text
manage_inventory
```

Prefer permissions such as:

```text
products.view
products.create
products.update
products.delete

sales.view
sales.create
sales.cancel
sales.refund

purchases.view
purchases.create
purchases.approve
purchases.receive
purchases.cancel

inventory.view
inventory.adjust
inventory.transfer
inventory.count

customers.view
customers.create
customers.update

suppliers.view
suppliers.create
suppliers.update

expenses.view
expenses.create
expenses.update
expenses.delete

reports.sales
reports.inventory
reports.financial
reports.profit_loss

users.view
users.create
users.update
users.delete

settings.business
settings.subscription
```

Inspect the existing permission system first and extend/refactor it rather than creating a competing authorization system.

---

# 8. DEFAULT ROLES

Support roles along these lines.

## Starter

Default:

### Owner

Owner has full permissions available to the Starter plan.

Starter owner should be able to perform all core business operations from the Management dashboard.

Additional staff can be invited with restricted permissions.

---

## Premium

Suggested roles:

### Owner

Full business access.

### Manager

Management + operational permissions according to assigned permissions.

### Cashier

Primarily:

* Create sales
* View products
* View customers
* Customer operations
* Returns if permitted

Should NOT automatically have:

* Stock adjustments
* Business settings
* Subscription management
* Financial controls

### Storekeeper

Primarily:

* Inventory
* Stock movements
* Receiving
* Stock counts
* Transfers where permitted

### Procurement Officer

Primarily:

* Reorder suggestions
* Suppliers
* Purchase orders
* Procurement history
* Receiving where permitted

These should be configurable rather than permanently hard-coded where the existing architecture allows it.

---

# 9. DASHBOARD ENTITLEMENTS

Implement a clean mechanism for determining whether a business can access a dashboard.

Conceptually:

```text
Starter
    management = true
    operations = false
    procurement = false

Premium
    management = true
    operations = true
    procurement = true

Enterprise
    management = true
    operations = true
    procurement = true
    enterprise capabilities = true
```

However, dashboard availability must NOT bypass user permissions.

There must be two separate checks:

```text
Can this business access this dashboard?
```

and:

```text
Can this user perform this action?
```

---

# 10. STARTER SPECIAL BEHAVIOR

This is extremely important.

Starter has only one dashboard:

**Management**

Therefore, do NOT hide operational functionality simply because it belongs conceptually to Operations or Procurement.

Instead, expose the relevant Starter functionality inside Management.

For example:

Management → Sales

Management → Purchases

Management → Inventory

Management → Products

Management → Suppliers

etc.

Starter should feel like one complete business-management application.

---

# 11. PREMIUM NAVIGATION

Premium should have clear dashboard separation.

Example:

```text
Management
  Overview
  Sales
  Purchases
  Inventory
  Cash Flow
  Reports
  Business Settings

Operations
  Sales
  Customers
  Inventory
  Stock Movements
  Returns
  Expenses

Procurement
  Overview
  Reorder Suggestions
  Purchase Orders
  Suppliers
  Receiving
  Procurement History
```

Do not duplicate business logic between dashboards.

Where the same underlying feature is used by multiple dashboards, reuse the same API/service/component where appropriate.

---

# 12. PROCUREMENT REORDER ENGINE

The Procurement dashboard must eventually support intelligent reorder suggestions.

For each product, calculate or expose:

* Current stock
* Reorder level
* Average daily sales
* Sales velocity
* Estimated days of stock remaining
* Suggested reorder quantity
* Last purchase price
* Preferred supplier
* Last purchase date

Example:

```text
Product: Blue Band 500g

Current Stock: 8
Average Daily Sales: 3.2
Days Remaining: ~2.5
Reorder Level: 15
Suggested Reorder: 25
```

The calculation must use actual existing sales/inventory data.

Do not fabricate values.

If the project already has a reorder-level system, integrate with it rather than creating a second inventory threshold system.

---

# 13. PURCHASE ORDER FLOW

Where purchase orders exist or are being introduced, support a clear lifecycle:

```text
Draft
↓
Pending
↓
Ordered
↓
Partially Received
↓
Received
```

Creating a purchase order must NOT automatically increase inventory.

Inventory should increase when stock is actually received.

Every stock-affecting event must be traceable.

---

# 14. STOCK MOVEMENTS

Strengthen inventory traceability.

Every stock change should have:

* Product
* Business
* Branch where applicable
* Quantity
* Movement type
* User
* Reference
* Reason
* Timestamp

Examples:

```text
+50 Purchase
-3 Sale
+10 Purchase
-1 Damage
-5 Adjustment
```

The system must make it possible to explain how the current stock quantity was reached.

---

# 15. AUDIT LOGGING

Introduce or strengthen audit logging.

Important actions should be traceable:

* Stock adjustments
* Product changes
* Price changes
* Purchase changes
* Sale cancellation
* Returns
* User changes
* Role changes
* Permission changes
* Business settings changes
* Financial changes

Audit records should contain appropriate context such as:

* User
* Business
* Branch
* Action
* Entity
* Entity ID
* Previous state where appropriate
* New state where appropriate
* Timestamp

Respect tenant isolation.

---

# 16. MULTI-TENANCY

This is a multi-tenant SaaS.

Do NOT compromise tenant isolation during the refactor.

Every business-specific resource must remain properly scoped to the authenticated business/tenant.

Where branches exist, respect branch-level access as well.

Never allow:

Business A → access Business B's data.

Also ensure role and permission checks cannot be used to bypass tenant boundaries.

---

# 17. FRONTEND

Refactor the React application to reflect the new architecture.

Create reusable concepts for:

* Dashboard layout
* Dashboard navigation
* Permission-aware navigation
* Plan-aware navigation
* Role-aware actions
* Protected routes
* Permission guards
* Feature visibility

Do not scatter plan checks everywhere like:

```tsx
if (plan === 'premium') ...
```

Create a centralized entitlement/authorization mechanism.

Likewise, avoid scattering role checks throughout the UI.

Prefer reusable abstractions such as:

```text
useCan()
useHasPermission()
useHasDashboard()
useFeature()
```

or equivalent patterns matching the existing project architecture.

---

# 18. BACKEND AUTHORIZATION

Backend authorization is authoritative.

Never rely solely on React to hide restricted functionality.

Every sensitive API endpoint must enforce:

1. Authentication
2. Tenant/business access
3. Dashboard/feature entitlement where applicable
4. User permission
5. Branch scope where applicable

Frontend restrictions are only for UX.

---

# 19. DATABASE

Before creating migrations:

Inspect existing tables.

Reuse existing:

* plans
* subscriptions
* roles
* permissions
* users
* businesses
* branches

where possible.

Only introduce new tables/columns where the existing architecture genuinely requires them.

Potential concepts may include:

```text
plans
plan_features / plan_entitlements
subscriptions
roles
permissions
role_permissions
user_roles
audit_logs
purchase_orders
purchase_order_items
```

But DO NOT blindly create these tables if equivalent structures already exist.

Adapt to the existing schema.

---

# 20. BACKWARD COMPATIBILITY

Existing working functionality must continue working.

Do not break:

* authentication
* tenant isolation
* sales
* purchases
* inventory
* cash flow
* reports
* subscriptions
* WhatsApp notifications
* existing APIs
* existing frontend flows

If an existing feature needs to change, refactor it carefully.

---

# 21. TESTING

After implementation, test at multiple levels.

## Starter

Test:

* Owner can access Management
* Owner can perform sales
* Owner can create purchases
* Owner can manage inventory
* Owner can manage products
* Owner can view reports
* Owner can manage business settings
* Staff cannot perform unauthorized actions

## Premium

Test:

* Owner can access all three dashboards
* Operations users cannot access restricted Management functions
* Procurement users can access Procurement
* Cashiers cannot adjust inventory unless explicitly permitted
* Procurement users cannot access unrelated financial controls

## Enterprise

Test:

* Enterprise capabilities are available
* Advanced roles work
* Branch restrictions work
* Audit logging works
* Tenant isolation remains intact

---

# 22. IMPORTANT DEVELOPMENT RULES

Before coding:

1. Inspect.
2. Understand.
3. Plan.
4. Refactor.
5. Test.
6. Fix regressions.

Do not perform a massive destructive rewrite.

Prefer small, coherent changes.

After each major backend change:

* Run migrations
* Run relevant tests
* Verify API behavior

After frontend changes:

* Run TypeScript checks
* Run build
* Verify routing
* Verify navigation
* Verify permission behavior

Do not leave dead code, duplicate authorization logic, or obsolete dashboard logic behind.

---

# 23. FINAL TARGET ARCHITECTURE

The final product should conceptually behave like this:

```text
                         AFRODUUKA
                             │
                     SUBSCRIPTION PLAN
                             │
             ┌───────────────┼───────────────┐
             │               │               │
          STARTER         PREMIUM        ENTERPRISE
             │               │               │
             ↓               ↓               ↓
        MANAGEMENT      MANAGEMENT       MANAGEMENT
                        OPERATIONS       OPERATIONS
                        PROCUREMENT      PROCUREMENT
                                             +
                                      Enterprise Features
             │               │               │
             └───────────────┼───────────────┘
                             ↓
                           RBAC
                             ↓
                           ROLE
                             ↓
                        PERMISSIONS
                             ↓
                           ACTION
                             ↓
                       BUSINESS DATA
```

The most important rule:

> **Plans determine what the business gets. Roles determine who can use it. Permissions determine what they can do.**

Implement this architecture cleanly on top of the existing AfroDuuka codebase without unnecessarily rewriting working functionality.

Start by inspecting the existing project and report the current architecture and the exact files/modules that will need to change before making the refactor.
