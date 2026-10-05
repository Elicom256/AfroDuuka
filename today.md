# Revenue Reconciliation for Sales Returns

## Problem Statement

The current system records sales returns in inventory but does not propagate the financial impact of those returns to other revenue-related modules. As a result, business revenue, analytics, cashflow records, dashboard metrics, and financial reports remain unchanged after a sales return is processed, leading to inaccurate accounting and misleading business insights.

### Example

A business sells:

- Phone A = UGX 200,000
- Phone B = UGX 200,000

**Total Sales Revenue = UGX 400,000**

If the customer returns one phone:

- Returned Value = UGX 200,000

**Expected Revenue = UGX 200,000**

If the customer returns both phones:

- Returned Value = UGX 400,000

**Expected Revenue = UGX 0**

All affected financial records, analytics, reports, and dashboard summaries must automatically reflect these changes.

---

## Required Behavior

### Revenue Adjustment

Whenever a Sales Return is created, updated, or deleted, the system must recalculate revenue using the original sale value of the returned item(s).

The adjustment must be reflected across all revenue-related modules, including but not limited to:

- Sales summaries
- Revenue reports
- Cashflow records
- Business analytics
- Dashboard metrics
- Profit calculations
- Any aggregated reporting tables derived from sales revenue

### Analytics Adjustment

Sales analytics must properly account for returned items and returned revenue.

The analytics layer must expose:

- Gross Sales
- Sales Returns
- Net Sales (Gross Sales - Returns)
- Returned Quantity
- Returned Revenue
- Return Rate

### Inventory Consistency

Returned products must continue following the existing inventory return workflow and stock adjustment logic already implemented within the system.

No existing inventory functionality should be broken or redesigned.

### UI Updates

Users must immediately see the financial impact of returns throughout the application.

This includes:

- Updated revenue figures
- Updated dashboard statistics
- Updated analytics reports
- Return-specific analytics and charts
- Accurate net sales calculations

---

## Acceptance Criteria

### Accounting

- [ ] Revenue decreases when a sales return is recorded.
- [ ] Revenue is restored if a sales return is reversed or deleted.
- [ ] Partial returns deduct only the returned portion.
- [ ] Full returns deduct the full value originally sold.
- [ ] Cashflow and revenue reports remain mathematically accurate.
- [ ] Financial reports consistently reflect net revenue.

### Analytics

- [ ] Sales Returns appear in analytics.
- [ ] Return Revenue is tracked separately from Gross Sales.
- [ ] Net Sales are calculated correctly.
- [ ] Return Rate is calculated correctly.
- [ ] All charts automatically reflect return activities.

### User Interface

- [ ] Revenue widgets update correctly after returns.
- [ ] Dashboard summaries reflect net revenue.
- [ ] Return metrics are visible in reporting sections.
- [ ] Users can clearly distinguish Gross Sales, Returns, and Net Sales.

---

## Technical Constraints

- Do not hallucinate or introduce new business rules.
- Preserve the existing architecture, design patterns, and workflows.
- Reuse existing services, models, events, observers, and relationships where possible.
- Edit migrations directly if schema changes are required.
- Avoid duplicate revenue calculations from multiple sources.
- Ensure all calculations remain transactional and consistent across the system.
- Use ShadCN components for new UI elements.
- Use Lucide React icons where appropriate.
- Use Chart.js for all analytics visualizations.

---

## Success Definition

After implementation, a Sales Return must behave as a negative sales transaction from a financial perspective.

Inventory, revenue, cashflow, reporting, dashboards, and analytics must remain synchronized and mathematically accurate across the entire platform, ensuring that business owners always see true net revenue after returns are processed.
