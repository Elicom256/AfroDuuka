# DuukaFlow Project — Admin Analytics

## Goal

Implement Business Reports

## Task

Implement components in src/app/pages/dashboards/admin/components/reports for business reports that correspond to apis in src/app/store/features/branch/reports/branchReportsQuery.ts
Each API should have an independent component, but they may have shared child components.
For periods, import them from src/app/pages/dashboards/admin/components/periodHelper.ts to avoid re-inventing the wheel
The parent component for reports is src/app/pages/dashboards/admin/pages/AdminReportsPage.tsx,
**VERDICT:** Good software === Nice implemented code that's scalable and re-usable

## Constraints

- ✅ Use real data as it's being fetched rtk
- ✅ Split code into components (no bloated parent).
- ✅ Use shadcn/ui for layout consistency.
- ✅ Keep UI/UX compact and dashboard-friendly.
- ✅ Render Components in the parent w.
- ✅ No hallucination — follow existing project patterns.

**Important** Take advanced decision in implementing this feature, and run commands to where needed
