# Plan: Integrate PriceAnalytics into PriceHistoryTab

## Goal
Remove the standalone `/admin/price-analytics` route and embed the `PriceAnalytics` component inside the existing `PriceHistoryTab` on the product detail page.

## Files to change

### 1. `ui/src/app/pages/dashboards/admin/components/products/PriceHistoryTab.tsx`

**Import changes:**
- Add import for `PriceAnalytics` from `'../analytics/PriceAnalytics'`
- Add import for `Accordion`, `AccordionContent`, `AccordionItem`, `AccordionTrigger` from `'@/components/ui/accordion'`
- Add `TrendingUp` to the lucide-react imports

**JSX changes (inside the return block, after the pagination):**
- Add an `Accordion` section below `</PaginationComponent>` and before `</CardContent>`:

```tsx
{/* Analytics section (collapsible) */}
<Accordion type="single" collapsible className="w-full">
  <AccordionItem value="analytics">
    <AccordionTrigger className="text-sm font-medium">
      <span className="flex items-center gap-2">
        <TrendingUp className="h-4 w-4" />
        Price Change Analytics
      </span>
    </AccordionTrigger>
    <AccordionContent className="pt-4">
      <PriceAnalytics />
    </AccordionContent>
  </AccordionItem>
</Accordion>
```

### 2. `ui/src/app/pages/dashboards/admin/pages/AdminPriceAnalyticsPage.tsx`
- **Delete this file entirely.** The `PriceAnalytics` component will now be rendered from inside `PriceHistoryTab` instead.

### 3. `ui/src/app/routes/AdminRoutes.tsx`
- **Remove** `import { AdminPriceAnalyticsPage } from '../pages/dashboards/admin/pages/AdminPriceAnalyticsPage';`
- **Remove** the route: `<Route path='price-analytics' element={<AdminPriceAnalyticsPage />} />`

### No changes needed
- `AdminSidebar.tsx` — the sidebar had no link to `/admin/price-analytics`, so nothing to remove.

## Result
- Price Analytics (store-wide trends) appears as a collapsible section within the Price History tab on any product's detail page.
- No orphan routes or dead sidebar items.
