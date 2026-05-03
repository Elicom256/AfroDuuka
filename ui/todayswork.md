 Today's work at DuukaFlow project
You are working inside an existing React + TypeScript frontend project using Vite, Redux Toolkit, and RTK Query. The Redux store and RTK Query setup are already configured.

Your task is to build a modern, responsive **Admin Dashboard** inside the folder:

`/dashboards/admin`

## Core Requirements

### 1. Tech & Libraries

* Use **React + TypeScript**
* Use **shadcn/ui components** for ALL UI (no custom raw Tailwind unless necessary)
* Use:

  * `Sonner` for toast notifications
  * `Sheet` for mobile sidebar
  * `Dialog` for forms (add/edit)
  * `Table`, `Button`, `Input`, `Card`, etc.
* Use **RTK Query** (already set up) for ALL data fetching and mutations
* Do NOT create mock data or new API slices

---

### 2. Environment Config
Use the existing environment variable:
`VITE_BASE_URL=http://localhost/api/`
Do NOT hardcode API URLs. Always use:
`import.meta.env.VITE_BASE_URL`

---

### 3. Admin Dashboard Layout

Create a clean, modern layout with:

* Sidebar (navigation)
* Header (top bar)
* Main content area

#### Sidebar

* Links:

  * Dashboard
  * Workers
  * (placeholder links: Products, Orders, and other modern links)

#### Responsiveness

* Desktop: fixed sidebar
* Mobile: sidebar inside `Sheet`

---

### 4. Workers Module (FULLY FUNCTIONAL)

This is the main working module and MUST be fully wired to RTK Query.

Use these existing hooks (DO NOT recreate them):

* `useGetWorkersInfoQuery`
* `useRegisterWorkerMutation`
* `useUpdateWorkerMutation`
* `useDeleteWorkerMutation`

#### Workers Page Features

##### Table

* Display workers using `Table`
* Columns:

  * Name
  * Email
  * Role
  * Actions

##### Fetching

* Use `useGetWorkersInfoQuery`
* Handle:

  * loading state
  * error state
  * empty state

##### Actions

**Add Worker**

* Button opens `Dialog`
* Form inputs (name, email, role, etc.)
* Submit → `useRegisterWorkerMutation`
* Show success/error toast using Sonner

**Edit Worker**

* Open Dialog with prefilled data
* Submit → `useUpdateWorkerMutation`

**Delete Worker**

* Trigger `useDeleteWorkerMutation`
* Show confirmation (simple)
* Show toast feedback

---

### 5. UX Requirements

* Show loading indicators (spinner or skeleton)
* Disable buttons while mutations are loading
* Show toast notifications for:

  * success
  * error
* Clean spacing using shadcn conventions
* Use Cards for layout sections

---

### 6. Code Structure

Organize files like:

`/dashboards/admin`

* `layout.tsx`
* `sidebar.tsx`
* `header.tsx`
* `/pages`

  * `dashboard.tsx`
  * `workers.tsx`
* `/components`

  * `workers-table.tsx`
  * `worker-form-dialog.tsx`

Keep components reusable and clean.

---

### 7. Important Constraints

* Do NOT generate fake/mock data
* Do NOT ignore RTK Query hooks
* Do NOT hardcode API URLs
* Do NOT build unresponsive layouts
* Follow clean, modern UI patterns (similar to shadcn dashboard style)

---

### 8. Optional (if time permits)

* Placeholder pages for Products, workers and Orders etc inside src/app/pages/dashboards/admin/components

---

Generate complete, clean, production-quality code.
 # Do NOT Hallucinate
