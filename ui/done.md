# Done

This' a Ugandan made modern inventory management system, for all kinds of shops.

## Todos

- Complete the Navbar
- Complete Home.tsx, About.tsx and Documentation.tsx, Footer.tsx
- Refactor whenever possible
- Use tailwindcss for styling, shadcn for styled components and lucide icons
- Don't re-invent the wheel yet we have shadcn's styled components
- make it more modern, UI/UX solid and mobile responsive
- For all the Four files (Home.tsx, About.tsx and Documentation.tsx, Footer.tsx), you can add components inside public/components and just import them in a specific file to file bloating with code
- in Footer.jsx, add socials to plas othermodern info then copyright thing

## Socials to add in Footer.tsx (add target \_blank)

https://github.com/Elijah-ug
https://x.com/ElicomElijah?t=9gAYJg6agmVW0GCKtYwPSA&s=08
https://www.linkedin.com/in/mugisha-elijah-88a291239/
https://elicomelijah.vercel.app/
https://wa.me/256781490899/?text=Hello%20Elicom%2C%20auto%20inserted/ (Whatsapp)

- Please, do not hallucinate
  Otherwise, let's make it modern

  # Today's work at DuukaFlow project
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

# Today's work at DuukaFlow project
 - in src/app/pages/dashboards/admin/sidebar.tsx, I have included some other features, but some don't have components they're linking to, eg Marketing etc, your work is to add corresponding pages and componets
 - Split code in each page into components if neccessary so that there is no too much code in 1 page(file)
 - Add corresponding querries inside a specific folder in src/app/store/features/business/ . Create the folder if not found
 - The baselink of the api being consummed is in the dotenv
 - Inside the addworker component, add phone and make role a selector
 * Inside every page linked, create a component of dummy data and render it where necessary
 - Donot make Footer.tsx global, ie it should not appear when in AdminDashboard

 ## Do NOT hallucinate and make it more modern

 1. **Create ProductTable component**
   - File: `src/app/pages/dashboards/admin/components/products/ProductTable.tsx`
   - Purpose: Display products in a tabular form.
   - Columns to include:
     - category_id
     - name
     - sku
     - barcode
     - price
     - cost_price
     - quantity
     - minimum_stock
     - status
     - description
     - category
   - Include action buttons: **edit** and **delete**.

2. **Integrate into Products page**
   - File: `src/app/pages/dashboards/admin/pages/products.tsx`
   - Render `ProductTable` as a child component.
   - Add links/buttons for:
     - **Add Product**
     - **Add Product Category**

3. **Category linkage**
   - Products must be linked to categories.
   - When adding a product, use a **select dropdown** populated with available categories.

4. **Pagination**
   - Create a reusable pagination component:
     - File: `src/app/utils/Pagination.tsx`
     - Use **shadcn pagination styles**.
   - Integrate pagination into `ProductTable`.

5. **Styling & Icons**
   - Use **shadcn UI components** for consistency.
   - Use **lucide-react icons** for actions (edit, delete, add).

6. **Code organization**
   - Split reusable UI into smaller components.
   - Keep product‑related components inside:
     - `src/app/pages/dashboards/admin/components/products`

