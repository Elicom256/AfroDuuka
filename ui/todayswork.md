# DuukaFlow Project — Admin Settings

## Goal

Implement the **Settings page** in the Admin dashboard.

## Tasks

1. **Create Settings Page**
    - Location: `src/app/pages/dashboards/admin/pages/settings.tsx`
    - Purpose: Centralized settings management for the business.

2. **Settings Components**
    - Each setting should be an independent component.
    - Location: `src/app/pages/dashboards/admin/components/settings/`
    - Components to implement:
        - PaymentSettings → manage payment methods [mobile money, card, cash, credit, crypto]
        - CustomerSettings → allow adding customers
        - SupplierSettings → allow adding suppliers
        - ReportsSettings → toggle business performance reports
        - PromotionsSettings → toggle promotions
        - AttendanceSettings → add attendance tracking for workers

3. **Store Integration**
    - Location: `src/app/store/features/business/settings/`
    - Create feature files for:
        - `attendance-settings`
        - `customer-settings`
        - `payment-settings`
        - `promotion-settings`
        - `reports-settings`
    - Base URL:
        ```js
        baseUrl: `${import.meta.env.VITE_BASE_URL}/settings`;
        ```

4. **Routing**
    - All settings routes should be nested under `/settings`.
    - Example:
        - `/settings/payment-settings`
        - `/settings/customer-settings`
        - `/settings/supplier-settings`
        - `/settings/reports-settings`
        - `/settings/promotions-settings`
        - `/settings/attendance-settings`

5. **UI & Design**
    - Use **shadcn UI components** for consistency.
    - Use **lucide-react icons** for settings navigation.
    - Split reusable UI into smaller components where necessary.

## Constraints

- ✅ Do not hallucinate features.
- ✅ Implementation must be consistent with **shadcn + lucide** design system.
- ✅ Follow feature owner engineering practices.
- ✅ Deliver working components, not static placeholders.
