# Launch Readiness Checklist Before Monetization

This system is close to being worth paying for, but it still needs several real business-critical features before launch. The main gap is not inventory tracking itself — it is the reliability of alerts, automation, and customer communication.

## 1. Must-Have Features Before Launch

### A. WhatsApp notification system (required for the product promise)

The app already advertises WhatsApp features in the public UI and in docs, but the backend is still incomplete. If this is in the plan, it must be real and configurable.

Required API models:

- WhatsAppConfig
  - business_id
  - phone_number_id
  - access_token
  - webhook_verify_token
  - is_active
  - default_template_id
  - provider (Meta/360dialog/WATI)
  - last_webhook_at

- WhatsAppTemplate
  - business_id
  - name
  - category (low_stock, daily_summary, payment, debt_reminder, welcome, promo)
  - locale
  - body
  - variables
  - status (approved, pending, rejected)
  - updated_by

- WhatsAppMessageLog
  - business_id
  - recipient
  - template_id
  - channel
  - message_body
  - variables
  - status (queued, sent, delivered, read, failed)
  - provider_response
  - sent_at
  - delivered_at
  - read_at
  - error_code

- NotificationPreference
  - user_id or business_id
  - channel (in_app, email, sms, whatsapp)
  - event_type
  - enabled
  - schedule_time

- CommunicationLog
  - business_id
  - customer_id
  - sale_id
  - type (whatsapp, sms, email)
  - provider
  - status
  - payload
  - sent_at

Required backend work:

- Laravel notification channel for WhatsApp
- Queue jobs with retries and backoff
- Webhook receiver for delivery/read status
- Template approval and validation flow
- Cron or event-based triggers for low stock, sales summary, payment reminders, debt notices, and shift alerts
- Admin config screen for API credentials, phone number, and test message

### B. Business alerts that justify the subscription price

The app should not only show in-app notifications. It should proactively create operational value.

Launch-worthy alert types:

- Low stock alerts
- Daily sales summary
- Purchase confirmation alerts
- Payment received confirmation
- Overdue debt reminders
- Refund anomaly alerts
- Stock expiry alerts
- Cash drawer mismatch alerts
- Staff attendance issues
- Suspicious sales pattern or duplicate receipt warnings

## 2. UI Changes Needed Before Launch

### Admin dashboard

- Notifications center should be polished and visible, not only a basic list.
- Add categories: Stock, Finance, Sales, Customers, Security.
- Add unread count, filters, and quick action buttons.
- Add a Settings page for message preferences and WhatsApp enablement.

### Manager / staff pages

The current UI still contains placeholder states and unfinished views. This weakens the product perception.

Fix or complete:

- Manager inventory pages
- Manager notifications pages
- Staff inventory pages
- Any dashboard sections that still say “will be implemented here”

### Customer-facing communications

- Digital receipt send flow
- WhatsApp receipt and payment reminder flow
- Customer purchase history and follow-up messaging
- Easy opt-in/opt-out configuration for customers

### Sales and operations UX

- Shift handover confirmation
- Drawer reconciliation summary with mismatch alerts
- Daily summary cards with readable business metrics
- “Why this matters” positioning on pricing and plans

## 3. API Changes Needed Before Launch

### Core backend readiness

- Real notification service abstraction: in-app, email, SMS, WhatsApp
- A provider adapter pattern (Meta WhatsApp / Twilio / SMS gateway)
- Job queue and failed-job handling
- Event-based triggers on sale, purchase, stock update, and finance changes
- Strong validation and audit logs around every outbound message

### Recommended endpoints

- POST /api/business/notifications/settings
- GET /api/business/notifications/logs
- POST /api/business/whatsapp/test
- POST /api/webhooks/whatsapp
- GET /api/notifications/unread-count
- POST /api/notifications/mark-all-read

### Reliability

- Retry logic on provider failures
- Exponential backoff
- Response logging for every message attempt
- Rate-limit protection to avoid blocked WhatsApp accounts
- Business-level failover for SMS/email if WhatsApp is down

## 4. What the product must look like to be premium

The system should feel like a “business operating system”, not a basic inventory tracker.

Premium value layer:

- Automated cash and stock control
- AI/manager summaries
- Customer retention messaging
- Smart low-stock recommendations
- Payment tracking and reminder sequences
- Shift reconciliation and anti-fraud controls
- Real communications to the owner/operator without manual checking

## 5. Launch Recommendation

Recommended order:

1. Fix UI placeholder pages and dashboard polish.
2. Build a real notification abstraction layer in the API.
3. Implement WhatsApp configuration + provider adapter.
4. Add message templates and queue-driven sending.
5. Add low-stock, sales summary, and payment reminder triggers.
6. Add logs, audit trails, and admin test flows.
7. Launch only after verifying the workflow end-to-end with a real business scenario.

### Post-launch follow-up: offline sync

Move offline-first POS sync to the first stabilization phase after launch so the initial release can focus on sale/stock/finance reliability without taking on a broad synchronization architecture too early.

Recommended post-launch scope:

- local pending transaction queue with unique local IDs
- server-side idempotent sync endpoints
- conflict policy for simultaneous changes during offline periods
- retry and failure handling for queued sync batches
- reconciliation reporting for partially synced transactions

This should happen near launch, but not block the launch itself.

## 6. Final Verdict

Without this layer, DuukaFlow is still a useful internal tool, but not yet a premium product. To justify paying for it, the app needs real operational automations, especially WhatsApp notifications, not just marketing claims or in-app notification storage.

This is the difference between a simple inventory app and a real retail operations platform.

<!-- ============== launch checklist ends here -->
