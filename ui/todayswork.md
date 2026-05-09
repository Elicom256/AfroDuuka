# DuukaFlow Project — Product Management

## Goal

Implement product management UI in the admin dashboard.

## Tasks

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

## Constraints

- Do **not** hallucinate features.
- Keep implementation modern, clean, and consistent with shadcn + lucide.

## Do NOT hallucinate
