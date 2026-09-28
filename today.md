**You're a staff full stack software engineer:** __

# AfroDuuka — Phase 0: Dashboard Naming + Procurement Foundation

We are preparing the existing AfroDuuka inventory SaaS for a larger subscription, dashboard and RBAC refactor.

Do NOT implement the full RBAC/subscription/dashboard architecture yet.

This phase has only two goals:

1. Rename the existing dashboard concepts consistently from backend → frontend.
2. Introduce a proper Procurement domain/model and its supporting backend/frontend foundation.

This is an existing production-oriented application. Do not rewrite working functionality unnecessarily.

---

# PART 1 — DASHBOARD RENAMING

We are replacing the old dashboard terminology with modern business terminology.

## Current → New

```text
Admin → Executive(Owner)
Manager      → Operations(Record sales, purchases, etc)
```

And introduce:

```text
Procurement
```

as a new dashboard/domain.

The terminology must be consistent across the entire application.

---

# 1. INSPECT BEFORE CHANGING

First inspect the project and identify every place where the existing dashboard names are used.

Search both backend and frontend for concepts related to:

```text
Admin
admin
Admin
admin
manager
Manager
dashboard
```

Do not blindly replace every occurrence of "admin".

Determine whether each occurrence refers to:

- dashboard
- role
- user type
- URL
- controller namespace
- middleware
- permission
- database record
- migration
- frontend route
- frontend component
- API endpoint
- navigation label
- policy
- notification
- test
- documentation

The goal is to rename the relevant concepts, not perform a dangerous global text replacement.

---

# 2. BACKEND RENAMING

Refactor backend naming where appropriate.

Inspect and update:

- Controllers
- Controller namespaces
- Models
- Model relationships
- Form requests
- Policies
- Middleware
- Services
- Actions
- Jobs
- Events/listeners
- Routes
- Route names
- Permissions
- Role names
- Enums
- Resources/Transformers
- Tests
- API documentation where applicable

The desired terminology should be:

```text
Executive->previously admin
Operations->previously manager
Procurement
```

For example, if the current architecture has:

```text
AdminController
```

determine whether it should become something equivalent to:

```text
ExecutiveController
```

If there is:

```text
ManagerController
```

and that controller actually represents the operational staff workspace, rename it appropriately to:

```text
OperationsController
```

Do not rename classes merely because they contain the word "admin" if they are actually framework/system infrastructure or Laravel's authentication/admin concepts.

---

# 3. DATABASE / MIGRATIONS

IMPORTANT:

Inspect whether existing migrations have already been executed in the development/production environment.

## If a migration has NOT been executed anywhere important

It may be renamed/refactored directly.

## If a migration has already been executed

Just edit it directly, the project is not yet in production
If existing role/dashboard records contain values such as:

```text
manager
admin
```

determine whether these represent actual persisted role identifiers.

If so, migrate the existing records safely to the new naming:

```text
admin → Executive
manager → operations
```

Do not create duplicate roles accidentally.

---

# 4. FRONTEND RENAMING

Refactor the React frontend to use:

```text
Executive - Replacing Currently Admin
Operations - Replacing currently Manager
Procurement - New
```

Update:

- routes
- route guards
- layouts
- sidebar navigation
- dashboard titles
- breadcrumbs
- page titles
- components
- hooks
- API client references
- permission checks
- dashboard redirects
- loading states
- error messages
- frontend types/interfaces
- tests

For example:

```text
/admin/dashboard
```

should NOT automatically be renamed if that would break existing API compatibility.

First determine whether `/admin` is merely a URL namespace or actually represents the Operations domain.

If URLs are changed, provide compatibility redirects/aliases where appropriate rather than breaking existing bookmarks or API consumers unnecessarily.

---

# 5. DASHBOARD STRUCTURE AFTER THIS PHASE

The application should conceptually have:

```text
Executive
Operations
Procurement
```

At this stage, do NOT implement the complete subscription logic yet.

We are only establishing the domain terminology and foundation.

---

# PART 2 — ADD PROCUREMENT DOMAIN

The application currently needs a dedicated Procurement domain.

Procurement should not simply be another name for Purchases.

The distinction is:

```text
Procurement
    ↓
What should we buy?
Who should we buy it from?
How much should we buy?
When should we buy it?
    ↓
Purchase Order
    ↓
Receiving
    ↓
Inventory
```

Whereas:

```text
Purchases
    ↓
Actual purchase/stock acquisition
```

Use the existing purchase/inventory architecture where possible.

Do NOT duplicate purchase logic.

---

# 6. INSPECT EXISTING PURCHASE ARCHITECTURE FIRST

Before creating Procurement models, inspect:

- Purchase model
- PurchaseItem model
- Supplier model
- Product model
- Branch model
- Inventory model
- Stock movement model
- Existing reorder-level fields
- Existing purchase controllers/services
- Existing stock receiving logic
- Existing supplier relationships

Reuse existing relationships and business logic.

Do not create duplicate versions of:

- products
- suppliers
- purchases
- inventory
- stock movements

---

# 7. PROCUREMENT MODEL

Introduce a Procurement domain/model appropriate to the existing architecture.

If the project already has a suitable Purchase Order concept, extend it rather than creating a duplicate model.

Otherwise introduce a model such as:

```text
PurchaseOrder
```

with an appropriate migration.

The model should support:

- business/tenant
- branch where applicable
- supplier
- created by
- approved by where applicable
- status
- order date
- expected delivery date
- notes
- totals
- timestamps

Suggested lifecycle:

```text
draft
pending
approved
ordered
partially_received
received
cancelled
```

Only use statuses that fit the existing business workflow.

Do not introduce unnecessary complexity if the current application does not need approval workflows yet.

---

# 8. PURCHASE ORDER ITEMS

Introduce a corresponding item model if one does not already exist.

For example:

```text
PurchaseOrderItem
```

It should support:

- purchase order
- product
- quantity
- unit cost
- total cost
- received quantity
- remaining quantity

Where appropriate, support:

```text
ordered quantity
received quantity
remaining quantity
```

Do not duplicate inventory quantities unnecessarily.

Inventory remains the source of truth for actual stock.

---

# 9. PROCUREMENT SERVICE / BUSINESS LOGIC

Create a clean service/action layer if the existing architecture uses services/actions.

Procurement should eventually support functions such as:

```text
getReorderSuggestions()
calculateSalesVelocity()
calculateStockCoverage()
calculateSuggestedOrderQuantity()
createPurchaseOrder()
approvePurchaseOrder()
markAsOrdered()
receivePurchaseOrder()
cancelPurchaseOrder()
```

Use the existing architecture's naming conventions.

Do not implement artificial "AI" forecasting at this stage.

The first version should use deterministic business data.

---

# 10. REORDER SUGGESTIONS

Procurement must eventually be able to identify products that need replenishment.

Use existing inventory and sales data.

Relevant inputs may include:

- current stock
- reorder level
- historical sales
- average daily sales
- sales velocity
- estimated days of stock remaining
- supplier
- last purchase price
- lead time if available

Example:

```text
Product: Blue Band 500g

Current Stock: 8
Reorder Level: 15
Average Daily Sales: 3.2
Estimated Days Remaining: 2.5
Suggested Order Quantity: 25
```

Do not hard-code these values.

Use actual database data.

If the existing product model already has:

```text
reorder_level
```

reuse it.

---

# 11. PROCUREMENT API

Add appropriate API endpoints following the existing API conventions.

Potential endpoints:

```text
GET    /procurement
GET    /procurement/reorder-suggestions

GET    /purchase-orders
POST   /purchase-orders

GET    /purchase-orders/{id}
PUT    /purchase-orders/{id}

POST   /purchase-orders/{id}/approve
POST   /purchase-orders/{id}/order
POST   /purchase-orders/{id}/receive
POST   /purchase-orders/{id}/cancel
```

Do NOT blindly use these exact URLs if the project has a different routing convention.

Follow the existing API architecture.

All endpoints must enforce:

- authentication
- tenant/business isolation
- branch access where applicable
- appropriate authorization

---

# 12. RECEIVING

Receiving a purchase order must integrate with the existing inventory system.

Important:

Creating a purchase order must NOT increase stock.

Stock should increase when inventory is actually received.

For partial receiving:

```text
Ordered: 100
Received: 60
Remaining: 40
```

The purchase order should become:

```text
partially_received
```

After the remaining 40 are received:

```text
received
```

Use the existing stock movement/inventory service rather than implementing a second stock calculation system.

---

# 13. FRONTEND PROCUREMENT FOUNDATION

Create the Procurement dashboard foundation in React.

At minimum establish:

```text
Procurement
├── Overview
├── Reorder Suggestions
├── Purchase Orders
├── Suppliers
└── Procurement History
```

Do not build every screen fully if the underlying backend functionality does not exist yet.

At this phase, establish:

- routes
- layout
- navigation
- page structure
- API types
- API client/service
- reusable components needed for procurement

---

# 14. PROCUREMENT OVERVIEW

The Procurement dashboard should eventually display:

- Products needing reorder
- Critical stock
- Suggested purchase value
- Pending purchase orders
- Recently ordered products
- Recently received stock

Keep the UI consistent with the existing AfroDuuka design system.

---

# 15. TENANT / BUSINESS ISOLATION

This is critical.

Every procurement record must belong to the correct business/tenant.

For example:

```text
Business A
    └── Purchase Order 1

Business B
    └── Purchase Order 2
```

Business A must never be able to access Purchase Order 2.

Respect branch-level isolation where applicable.

Never trust a business_id supplied blindly by the frontend.

Derive tenant context from the authenticated user/business context used by the existing application.

---

# 16. AUTHORIZATION FOUNDATION

Do not implement the complete RBAC refactor yet.

However, Procurement must be designed so that authorization can later support permissions such as:

```text
procurement.view
procurement.create
procurement.update
procurement.approve
procurement.receive
procurement.cancel

purchase_orders.view
purchase_orders.create
purchase_orders.update
purchase_orders.approve
purchase_orders.receive
```

If an existing permission system already exists, integrate with it.

Do not create a second permission framework.

---

# 17. TESTING

Before finishing this phase:

### Dashboard naming

Verify:

- Executive dashboard works
- Operations dashboard works
- Existing users/roles still authenticate
- Existing redirects work
- Existing API endpoints are not unnecessarily broken
- Existing frontend navigation works

### Procurement

Verify:

- Procurement records are tenant-scoped
- Purchase orders can be created
- Purchase order items work
- Status transitions work
- Partial receiving works
- Full receiving works
- Inventory updates only when stock is received
- Reorder suggestions use real sales/inventory data
- Unauthorized users cannot perform restricted procurement actions

Run:

- backend tests
- frontend tests
- TypeScript checks
- production build

Fix regressions before moving on.

---

# 18. DO NOT DO YET

Do NOT implement these in this phase unless required to support the above:

- Complete subscription refactor
- Complete RBAC refactor
- Enterprise plan
- Custom roles
- Advanced analytics
- AI forecasting
- New WhatsApp features
- Major UI redesign
- Unrelated modules

Those will be handled module-by-module afterward.

---

# FINAL ACCEPTANCE CRITERIA

When this phase is complete:

## Naming

The application consistently understands:

```text
Executive
Operations
Procurement
```

instead of the old dashboard terminology.

## Procurement

There is a proper Procurement foundation connected to:

```text
Business
Branch
Supplier
Product
Purchase Order
Purchase Order Items
Inventory
Stock Movements
Sales history
```

with appropriate business logic.

## Architecture

The code is ready for the next phase:

```text
Subscription
      ↓
Dashboard Entitlements
      ↓
RBAC
      ↓
Permissions
      ↓
Business Operations
```

Do not proceed into the larger RBAC/subscription refactor until this phase is stable.

At the beginning, report:

1. Current dashboard architecture
2. Current role/permission architecture
3. Existing purchase/inventory architecture
4. Existing models that can be reused
5. Files that will be changed
6. Database migrations that are required
7. Any naming conflicts or risky changes discovered

Then implement the changes incrementally.
**NB:** *First check if a module has been implemented already to avoid duplication or unnecessary work*
 *Manager and Admin have not yet been fully renamed to what we need, so rename them wholy, meaning we expect no file that has the word admin in it*
