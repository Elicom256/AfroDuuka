# DuukaFlow Project — Admin Analytics

## Goal

Implement the `AdminAnalyticsPage` with modular analytics components.  
Each analytic (Sales, Purchases, Inventory, Customers, Suppliers) should live in its own component file, e.g. `SalesAnalytics.tsx`.

## Task

- Create `AdminAnalyticsPage.tsx` as the parent container.
- Inside `src/app/pages/dashboards/admin/components/analytics/`, add child components:
  - `SalesAnalytics.tsx`
  - `PurchasesAnalytics.tsx`
  - `InventoryAnalytics.tsx`
  - `CustomersAnalytics.tsx`
  - `SuppliersAnalytics.tsx`
- Each child component should:
  - Use **shadcn/ui Card** as the wrapper.
  - Render a **Chart.js** visualization via `react-chartjs-2`.
  - Accept props for data (e.g. salesData, purchaseData).
  - Keep styling compact (icons `h-6 w-6`, values `text-xl`).
- `AdminAnalyticsPage.tsx` should import and render all child components in a responsive grid.

## Constraints

- ✅ Use real data as it's being fetched in store
- ✅ Split code into components (no bloated parent).
- ✅ Use shadcn/ui for layout consistency.
- ✅ Use react-chartjs-2 for charts.
- ✅ Keep UI/UX compact and dashboard-friendly.
- ✅ No hallucination — follow existing project patterns.

## Example

```tsx
// SalesAnalytics.tsx
import { Card, CardContent } from '@/components/ui/card';
import { Bar } from 'react-chartjs-2';

export const SalesAnalytics = ({ salesData }) => {
  const chartData = {
    labels: salesData.map((s) => s.month),
    datasets: [
      {
        label: 'Sales',
        data: salesData.map((s) => s.total),
        backgroundColor: 'rgba(75,192,192,0.6)',
      },
    ],
  };

  return (
    <Card>
      <CardContent>
        <h3 className='text-sm font-semibold mb-2'>Monthly Sales</h3>
        <Bar data={chartData} />
      </CardContent>
    </Card>
  );
};
```
