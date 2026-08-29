# Todo: WhatsApp Module Audit, Revision & Refactor Plan

## Objective

Audit the existing WhatsApp module in **DuukaFlow** and determine whether it is properly aligned with the project's business requirements and architecture.

Do **not** immediately rewrite the module.

First:

1. Inspect the existing WhatsApp implementation end-to-end.
2. Identify what is already implemented.
3. Compare it against the requirements below.
4. Identify missing functionality, architectural problems, duplication, weak abstractions, incorrect business logic, and scalability/reliability concerns.
5. Produce a clear refactoring plan.
6. Only after the plan is established should implementation/refactoring begin.

The refactored module should be production-oriented, maintainable, queue-friendly, and consistent with the existing DuukaFlow architecture.

---

# WhatsApp Integration Requirements

WhatsApp should be used for **important business notifications and business-critical events**, not for unnecessary or excessive messaging.

## 1. Business Registration

When a business successfully registers on DuukaFlow:

* Send a WhatsApp notification confirming successful registration.
* Identify the business appropriately.
* The notification should be sent asynchronously where appropriate.
* Avoid sending the notification before the business registration transaction has successfully completed.

Example purpose:

> Welcome the business to DuukaFlow and confirm that the account has been successfully created.

---

## 2. Subscription & Payment Events

Send WhatsApp notifications for important subscription events:

### New Subscription

When a business successfully subscribes to a plan:

* Notify the business.
* Include the subscribed plan.
* Include relevant subscription/payment information.
* Include subscription start and expiry dates where appropriate.

### Plan Change

When a business changes its subscription plan:

* Notify the business.
* Clearly indicate the new plan.
* Include the effective date where applicable.

### Subscription Renewal

When a subscription is successfully renewed:

* Notify the business.
* Include the renewed plan and new subscription period.

### Payment Failure / Unsuccessful Payment

If the current subscription/payment architecture supports failed payments:

* Notify the business when a payment that should activate/renew a subscription fails.
* Do not treat a failed payment as a successful subscription event.

---

## 3. Subscription Expiry

When a business subscription expires:

* Notify the business that the subscription has expired.
* Explain that access/features may be restricted according to the application's subscription rules.
* Provide the appropriate action required to reactivate the subscription.

### Expired Subscription Reminders

After expiry:

* Send a reminder every **2 days** while the business does not have an active subscription.
* Stop sending reminders immediately once the business has an active subscription again.
* Avoid duplicate reminders for the same reminder period.
* This should be implemented using scheduled/background jobs rather than a synchronous request.

The system should be designed so that the reminder process is safe to run repeatedly without creating duplicate notifications.

---

## 4. Free Trial / Free Period Expiry

When a newly registered business reaches the end of its free period:

* Notify the business that the free period has ended.
* Encourage the business to subscribe to a paid plan.
* Include the relevant subscription action/information.

If the business has already subscribed, do not send a free-period-expiry notification incorrectly.

This event should be handled automatically through scheduled/background processing.

---

## 5. Monthly Business Performance Report

Send the business a monthly WhatsApp performance summary.

The report should contain useful high-level business metrics available from the existing reporting/analytics system, such as:

* Total sales/revenue
* Total purchases
* Expenses where applicable
* Profit/loss where available
* Number of sales
* Number of purchases
* Inventory-related highlights
* Other important business KPIs already supported by DuukaFlow

Do not duplicate business calculations unnecessarily inside the WhatsApp module.

The WhatsApp report should consume existing analytics/reporting services where possible.

### Important

Determine:

* The appropriate date range for the monthly report.
* Whether the report should represent the previous completed month.
* The appropriate sending date/time.
* Whether the business's timezone should be respected.
* How businesses with multiple branches should be handled.
* Whether the report should contain consolidated business-level figures or branch-level figures.

The implementation should be configurable rather than hard-coded where practical.

---

## 6. Purchase Orders & Sales Orders

When the business receives a new:

* Purchase Order
* Sales Order

send a WhatsApp notification to the appropriate business recipient.

The notification should contain enough information to understand the event without unnecessarily exposing excessive data.

Determine from the existing order architecture:

* What constitutes a "new order".
* Who the recipient should be.
* Whether the notification should go to the business owner, administrator, branch manager, or another configured WhatsApp recipient.
* Whether branch-specific orders should notify only the relevant branch recipient or the entire business.

Do not assume the recipient model without inspecting the existing architecture.

---

## 7. Inventory Alerts

Send WhatsApp notifications when inventory requires attention.

### Low Stock

When a product's stock falls below its configured threshold:

* Notify the appropriate business/branch recipient.
* Include the product.
* Include current quantity.
* Include the configured threshold.
* Identify the relevant branch where applicable.

### Out of Stock

When a product reaches `0` stock:

* Send an out-of-stock notification.
* Avoid repeatedly sending the same alert every time the system checks the inventory.

### Important

The implementation should be **event/state based**, not simply "send a message every time a stock check runs."

For example:

```text
10 units → 4 units
```

If the threshold is 5, trigger a low-stock alert.

But repeated background checks while the quantity remains at 4 should not continuously send duplicate messages.

Likewise, an out-of-stock alert should not be sent repeatedly while the product remains at 0.

Determine the appropriate mechanism for resetting/rearming alerts when stock is replenished.

---

# Background Jobs & Asynchronous Processing

Most WhatsApp notifications should be generated and sent through **background jobs/queues**.

The normal application request should not unnecessarily wait for WhatsApp API/network operations.

Evaluate the existing queue/job infrastructure and determine:

* Which notifications should be queued.
* Which jobs already exist.
* Whether jobs can be retried safely.
* How failed WhatsApp messages are handled.
* Whether retries can result in duplicate messages.
* Whether jobs should use idempotency/deduplication mechanisms.
* Whether failed notifications should be logged for later inspection.

Scheduled events such as:

* Subscription expiry reminders
* Free-period expiry
* Monthly reports

should use the application's scheduler/cron infrastructure.

---

# WhatsApp Module Architecture Audit

Inspect the existing module and specifically evaluate:

## Provider/API Integration

Determine:

* Which WhatsApp provider/API is currently being used.
* How authentication/configuration is handled.
* Whether API credentials are stored securely.
* Whether the provider integration is isolated behind a service/interface.
* Whether changing WhatsApp providers in the future would require minimal application changes.

Prefer a provider abstraction where appropriate.

---

## Notification Architecture

Determine whether the current implementation has appropriate separation between:

```text
Business Event
      ↓
Notification Decision
      ↓
Queue / Job
      ↓
WhatsApp Service
      ↓
WhatsApp Provider/API
```

Avoid tightly coupling business modules directly to WhatsApp API calls.

For example:

```text
Sales module
    ❌ directly calls WhatsApp API

Sales module
    ↓
Domain/business event
    ↓
WhatsApp notification job
    ↓
WhatsApp service
    ↓
Provider
```

Use the existing project's architecture where possible rather than introducing unnecessary complexity.

---

# Templates & Message Management

Audit how WhatsApp messages are currently generated.

Determine whether message content should be:

* centralized,
* template-based,
* reusable,
* parameterized,
* provider-compatible.

Avoid scattering WhatsApp message strings throughout controllers, models, jobs, and services.

Consider a structure such as:

```text
Registration
Subscription
Subscription Renewal
Subscription Change
Subscription Expiry
Free Period Expiry
Monthly Report
New Purchase Order
New Sales Order
Low Stock
Out of Stock
```

The exact implementation should follow the existing project's conventions.

---

# Recipients & WhatsApp Numbers

Audit how the system determines the WhatsApp recipient.

Determine:

* Where business WhatsApp numbers are stored.
* Whether a business can have multiple notification recipients.
* Whether branch-level recipients are supported.
* Whether recipient preferences exist.
* Whether a recipient can opt out of specific notification types.
* How phone numbers are normalized.
* How invalid/missing numbers are handled.

Do not assume the business owner's personal phone number is always the correct recipient.

---

# Notification Preferences

Evaluate whether DuukaFlow needs configurable notification preferences.

Consider allowing businesses to control notification categories such as:

```text
☑ Subscription notifications
☑ Inventory alerts
☑ Order notifications
☑ Monthly reports
☑ System/account notifications
```

Determine whether this is already supported and, if not, whether it should be included in the refactor.

Do not introduce a complex preference system unless it is justified by the existing architecture and requirements.

---

# Reliability & Observability

The WhatsApp system should be designed for real-world failures.

Audit whether the current module supports:

* API failures
* timeouts
* invalid phone numbers
* rate limits
* provider downtime
* failed jobs
* retries
* duplicate prevention
* message status tracking
* useful application logs

Determine whether a notification/message record should be persisted.

If appropriate, consider a notification/message log containing information such as:

```text
notification type
business
branch (if applicable)
recipient
message/template
provider
status
attempt count
provider message ID
error
sent_at
failed_at
created_at
```

Do not add fields that are unnecessary; base the final design on the existing implementation.

---

# Business & Branch Scope

DuukaFlow supports businesses and business branches.

Audit every WhatsApp notification and determine whether it belongs to:

* the entire business,
* a specific branch,
* both.

Examples:

```text
Subscription → Business level

Monthly business report → Business level

Low stock → Branch level

New branch order → Relevant branch + business recipient where appropriate
```

Do not hard-code this assumption; verify it against the existing domain architecture.

---

# Duplicate Notification Prevention

The refactored system must prevent accidental duplicate notifications.

Pay particular attention to:

* queued jobs
* job retries
* scheduled commands
* subscription reminders
* monthly reports
* stock alerts
* event listeners

A retry of a failed job should not blindly create/send duplicate business notifications if the original message may already have succeeded.

Evaluate appropriate idempotency/deduplication strategies.

---

# Configuration

Audit all WhatsApp-related configuration.

Determine what should be configurable through:

* `.env`
* application configuration
* database settings
* business settings

Do not hard-code:

* API credentials
* phone numbers
* provider URLs
* message limits
* retry values
* scheduling assumptions

Secrets must never be committed to source control.

---

# Audit Deliverable

Before changing code, produce a report with the following structure:

## 1. Current Implementation

Explain how the existing WhatsApp module currently works.

## 2. Requirement Coverage

Create a table:

| Requirement         | Current Status | Existing Implementation | Missing/Problem |
| ------------------- | -------------- | ----------------------- | --------------- |
| Registration        |                |                         |                 |
| Subscription        |                |                         |                 |
| Plan Change         |                |                         |                 |
| Renewal             |                |                         |                 |
| Payment Failure     |                |                         |                 |
| Subscription Expiry |                |                         |                 |
| Expiry Reminders    |                |                         |                 |
| Free Period Expiry  |                |                         |                 |
| Monthly Report      |                |                         |                 |
| Purchase Orders     |                |                         |                 |
| Sales Orders        |                |                         |                 |
| Low Stock           |                |                         |                 |
| Out of Stock        |                |                         |                 |

Use statuses such as:

* ✅ Complete
* 🟡 Partially implemented
* ❌ Missing
* ⚠️ Implemented but needs refactoring

## 3. Architectural Problems

Identify:

* tight coupling
* duplication
* poor separation of concerns
* missing abstractions
* scalability issues
* queue/job issues
* scheduling issues
* database issues
* reliability issues

## 4. Missing Features

List everything required by the business plan that does not currently exist.

## 5. Refactoring Plan

Provide the proposed architecture and implementation steps.

Prioritize:

1. Critical correctness/reliability issues
2. Missing business requirements
3. Queue/background processing
4. Notification architecture
5. Templates
6. Recipient management
7. Deduplication/idempotency
8. Logging/observability
9. Preferences/configuration
10. Cleanup and optimization

## 6. Database Changes

List any required migrations/models/indexes/relationships.

## 7. Jobs, Events & Scheduled Tasks

List the proposed:

* events
* listeners
* jobs
* commands
* scheduled tasks

and explain their responsibilities.

## 8. Testing Plan

Identify tests required for:

* successful notifications
* failed API requests
* retries
* duplicate prevention
* subscription expiry
* reminder scheduling
* free-period expiry
* monthly reports
* stock alerts
* branch scoping
* recipient selection

---

# Implementation Rules

Before making significant changes:

* Inspect the existing codebase.
* Reuse existing services, models, events, jobs, analytics, and infrastructure where appropriate.
* Do not duplicate existing business logic.
* Do not create unnecessary abstractions.
* Follow the project's existing Laravel conventions.
* Preserve existing functionality unless it conflicts with the requirements.
* Do not modify unrelated modules.
* Keep database queries efficient.
* Ensure queued jobs are safe to retry.
* Ensure scheduled tasks are safe to run repeatedly.
* Keep business-level and branch-level data properly scoped.
* Never expose WhatsApp/API credentials in logs or source code.

## Important

**First complete the audit and refactoring plan.**

Do not begin a large-scale refactor until the audit clearly explains:

1. What exists.
2. What is missing.
3. What is wrong.
4. What should change.
5. Why the proposed architecture is appropriate for DuukaFlow.

After the audit, proceed with implementation in small, verifiable stages.

