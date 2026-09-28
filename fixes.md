# Fixes and launch blockers

## Release-blocking items before launch

These are the items that must be completed before monetization.

- Notification abstraction and provider adapters
- Template approval and validation
- Queue failures and backoff
- Admin settings screens
- Alert triggers and customer communications
- Polish on remaining dashboard/UI placeholders
- Real end-to-end business verification

## Detailed plan

### 1. Notification abstraction and provider adapters

Status: already implemented in the API codebase; this is the one item that is effectively in ship mode.

- Real notification abstraction layer already exists for in-app, email, SMS, and WhatsApp via the channel registry and dispatch pipeline.
- Provider adapters already exist for Meta WhatsApp and SES-style email flows.
- Demo/fallback behavior remains for local testing and non-production use.
- Outbound notifications already route through a single standard pipeline.

Verification note:

- The current blocker is not missing abstraction code; it is environment readiness for the real provider and database setup.
- The focused test run failed because the PostgreSQL host `pgsql` could not resolve in this environment, which prevented full end-to-end verification.

### 2. Template approval and validation

- Add approval and validation flow for WhatsApp and email templates.
- Support states such as pending, approved, rejected, and failed.
- Validate variable placeholders, locale, and provider-specific formatting before sending.
- Prevent invalid templates from being sent to customers.

### 3. Queue failures and backoff

- Add retry logic for provider failures.
- Implement exponential backoff and failed-job tracking.
- Log queue failures with admin-visible alerts for critical notifications.
- Prevent silent drop of important messages.

### 4. Admin settings screens

- Create admin configuration screens for WhatsApp credentials, SES config, and notification preferences.
- Add business-level defaults, channel toggles, test-message actions, and setup validation.
- Keep settings scoped correctly by business and branch.

### 5. Alert triggers and customer communications

- Implement stock, payment, debt, refund, and sales-summary alert triggers.
- Add customer-facing communication workflows for receipts, reminders, and follow-ups.
- Ensure all alerts and messages are logged and attributable to an event or user.

### 6. Dashboard and UI polish

- Complete any remaining dashboard placeholders.
- Finish manager and staff inventory and notification screens.
- Remove unfinished “to be implemented later” states that reduce launch confidence.

### 7. Real end-to-end business verification

- Verify message sending from trigger to provider to log.
- Test stock alerts, payment reminders, receipts, and admin settings.
- Validate the launch flow in a real business scenario before opening to customers.

## Launch gate

Do not launch until each item above is implemented, tested, and verified in a real business flow.

## Related file

See [launch-list.md](launch-list.md) for the compact release checklist.
