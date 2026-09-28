# UI dashboard plan

## Priority order

1. Business admin dashboard
2. Business branch admin dashboard
3. Employee dashboard
4. System dashboard

## Start here

### 1) Business admin dashboard

This is the main operational dashboard for the business owner and should be the first UI we finish.

Why first:

- it is the highest-value screen for running a business
- it already exists in the codebase and is wired into the main admin route
- it covers overview, stock, finance, reports, and messaging flows

Relevant files:

- ui/src/app/routes/AdminRoutes.tsx
- ui/src/app/pages/dashboards/admin/AdminLayout.tsx
- ui/src/app/pages/dashboards/admin/pages/AdminDashboardPage.tsx

*NB* Use lucide icons and shadcn for UI/UX designs where possible
Minimum first pass:

- clean overview cards
- high-signal KPIs
- stock health section
- sales + purchases summary
- recent alerts / notifications
- AI panel or summary panel

### 2) Business branch admin dashboard

This is the branch-level dashboard used by a branch manager or branch admin.

Why second:

- it is the next operational layer after the business owner dashboard
- it must reflect branch-level stock, sales, payments, and staffing status
- it carries the day-to-day operational workload

Relevant files:

- ui/src/app/routes/ManagerRoutes.tsx
- ui/src/app/pages/dashboards/manager/ManagerLayout.tsx
- ui/src/app/pages/dashboards/manager/pages/ManagerDashboardPage.tsx

Minimum first pass:

- total sales
- purchases
- inventory count
- low-stock and branch alerts
- branch summary cards
- recent activity table

### 3) Employee dashboard

This is for staff like guards, cleaners, cashiers, or general employees.

Focus:

- check-in / attendance
- task list
- quick stock view if needed
- limited admin actions only

This should wait until the operational business and branch dashboards are done.

### 4) System dashboard

This is for the personal/system-level dashboard for the app owner or engineer/admin.

Focus:

- system status
- business health
- subscription data
- platform-wide issues
- settings and operational control

This is useful but should come after the business-facing dashboards are stable.

## Recommended working order

1. Finish the admin dashboard shell and summary cards.
2. Fix the manager branch dashboard so it can run in real branch contexts.
3. Only then work on employee and system dashboards.

## Practical recommendation

Start with the already-existing admin and superadmin dashboards because they are already scaffolded and connected to data. Do not spend time on employee/system dashboards before the core business-workflow screens are polished.
