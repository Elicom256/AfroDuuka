# WhatsApp Implementation Blueprint

## Purpose

This document defines the architecture and implementation rules for the DuukaFlow WhatsApp module during Phase 1. It is the reference document to follow before writing code and should be used as the source of truth for the first implementation pass.

The goal is to establish a production-oriented, queue-friendly WhatsApp notification foundation without breaking existing functionality.

---

## 1. Architectural Goal

The module should follow this flow:

Business Event
-> Notification Decision
-> WhatsAppNotification payload
-> queued job
-> WhatsApp service
-> provider adapter
-> message log + status tracking

This keeps business logic separate from transport logic and avoids hardcoding message logic inside unrelated modules.

---

## 2. Core Principles

### 2.1 Separation of Concerns

- Business modules should raise events or dispatch notification requests.
- WhatsApp-specific handling should live inside WhatsApp-specific classes.
- Delivery to provider APIs should be isolated behind a provider abstraction.

### 2.2 Queue-first Delivery

- Normal requests should not wait on WhatsApp network calls.
- All real WhatsApp sends should go through queued jobs.
- Retry-safe design should be preferred over synchronous immediate sends.

### 2.3 Idempotency and Preventing Duplicate Sends

- Notifications must be deduplicated by business event and message signature.
- Repeated job execution should not result in duplicate messages.
- We should implement dedupe keys for high-risk operations like inventory alerts and expiry reminders.

### 2.4 Demo-safe Fallback

- Demo mode must remain available as a non-production fallback.
- Production behavior should be default when config is valid.
- Demo sends should never be the only delivery path in production-grade logic.

### 2.5 Backward Compatibility

- Existing controller endpoints and UI settings must remain functional.
- Existing business modules must not break while new WhatsApp architecture is introduced.

---

## 3. Module Responsibilities

### 3.1 Models

Existing models to preserve:

- WhatsAppConfig
- WhatsAppTemplate
- WhatsAppMessageLog

Phase 1 adds no major new model unless needed for the queue foundation. We should keep the model structure minimal and consistent with existing Laravel conventions.

### 3.2 Services

We will introduce a clean WhatsApp service layer with clear boundaries:

- WhatsAppService
  - resolves business config
  - resolves default templates
  - orchestrates queue/send flow
  - handles demo-safe fallback

- WhatsAppNotificationService
  - central message dispatch logic
  - validates recipients, templates, and payloads
  - creates notification request objects

- WhatsAppTemplateService
  - loads stored templates by category and key
  - resolves variables and formatting
  - returns message body for sending

- WhatsAppProviderFactory / ProviderResolver
  - selects correct provider implementation based on config

### 3.3 Providers

We will abstract the actual WhatsApp transport behind an interface.

Interface responsibilities:

- sendMessage(payload)
- getName()
- supportsTemplate()
- validateConfiguration()

Implementations:

- DemoWhatsAppProvider
- MetaWhatsAppProvider (future-ready stub, not mandatory in first pass)

The interface should be the only direct point of contact with provider-specific code.

### 3.4 Jobs

Phase 1 should introduce a queue-based flow like this:

- ProcessWhatsAppNotificationJob
  - main sending job
  - loads notification payload
  - checks dedupe
  - calls provider adapter
  - updates message logs

Other jobs may exist later, but the initial job architecture must already support retry and status tracking.

### 3.5 Events / Notifications

A generic event payload should be created, not hardcoded send logic.

Example structure:

- type: registration, subscription, renewal, expiry, inventory, order
- business_id
- branch_id
- recipient_phone
- template_key
- template_data
- dedupe_key
- metadata

This event object will be queued and processed with a provider-independent payload.

---

## 4. Recommended Class Structure

### 4.1 App structure

api/app/
Services/WhatsApp/
Contracts/
WhatsAppProviderInterface.php
WhatsAppService.php
WhatsAppNotificationService.php
WhatsAppTemplateService.php
WhatsAppProviderFactory.php
Providers/
DemoWhatsAppProvider.php
MetaWhatsAppProvider.php (future)
Jobs/
ProcessWhatsAppNotificationJob.php
Events/
WhatsAppNotificationCreated.php
Http/Controllers/
WhatsAppConfigController.php (existing)

### 4.2 Why this structure

This structure gives us:

- provider independence
- queue-friendly dispatch
- a single place for business notification orchestration
- easier future extension to Meta, 360dialog, or any new provider

---

## 5. Database and Data Model Guidance

### 5.1 Existing tables

We should keep working with the current WhatsApp tables:

- whats_app_configs
- whats_app_templates
- whats_app_message_logs

### 5.2 Minimal Phase 1 schema expectations

At minimum, the message log should include:

- id
- business_id
- branch_id (nullable)
- template_key
- category
- recipient_phone
- message_body
- status
- provider_name
- provider_message_id (nullable)
- error_message (nullable)
- payload_json
- dedupe_key
- sent_at
- created_at
- updated_at

If a migration is needed, it should only extend the current log model with fields required for reliable delivery tracking and deduplication. No large schema redesign should happen in Phase 1.

### 5.3 Deduplication Strategy

For Phase 1, dedupe should be keyed by a deterministic string based on:

- notification category
- business id
- branch id
- event type
- event id or aggregate key
- template key

Example:

notification:inventory:low-stock:business-7:branch-3:product-14

The same job or event should not send twice if retried or re-triggered under the same event identity.

---

## 6. Notification Flow Design

### 6.1 Event-driven pipeline

Example path for low-stock alert:

1. Product stock changes
2. Inventory service determines stock is below threshold
3. Domain logic emits or dispatches a WhatsApp notification request
4. WhatsAppNotificationService normalizes payload and template data
5. A dedupe key is generated
6. ProcessWhatsAppNotificationJob is queued
7. Job loads config and provider
8. Provider sends to WhatsApp API
9. Message log records status

### 6.2 Only send after successful business action

No notification should be created before the source transaction is confirmed. The notification should be raised only after the business action has succeeded and persisted.

### 6.3 No direct business logic in the provider layer

The provider must never decide business rules such as:

- whether a notification should be sent
- whether a stock threshold qualifies
- whether a subscription is active

Those decisions belong to the business/domain layer.

---

## 7. Template System Design

### 7.1 Template source

Templates should be stored in the database and resolved through a service layer.

Template categories for Phase 1:

- registration
- subscription
- renewal
- expiry
- inventory
- order

### 7.2 Template contract

Each template should support:

- key
- category
- subject or label
- body
- variables map
- active flag

### 7.3 Variable replacement

Use a parameter replacement system, not hardcoded conditional concatenation.

Example:

"Hello {business_name}, your plan {plan_name} is active until {expiry_date}."

This keeps the message consistent and easier to manage in the future.

---

## 8. Provider Abstraction Design

### 8.1 Interface

The provider abstraction should look like this conceptually:

- sendMessage(array $payload): array
- validateConfiguration(array $config): bool
- getName(): string

The interface must not leak business logic or app-specific behavior. It should only act as a transport adapter.

### 8.2 Demo provider behavior

The demo provider should simulate sending safely in non-production or config-disabled mode.

It should:

- return success-like payloads for testing
- log the outgoing payload
- not block app flow
- never be the only mechanism in production settings

### 8.3 Future compatibility

Later providers like Meta can be added by creating a new adapter implementing the same interface. The app should not need to change business logic, only the provider implementation.

---

## 9. Queue and Retry Design

### 9.1 Job responsibilities

ProcessWhatsAppNotificationJob should:

- check dedupe key
- resolve business config
- resolve provider
- prepare message body
- send
- write message log
- mark success/failure
- retry only if safe

### 9.2 Safe retries

Retries are safe only when the message is idempotent and the dedupe key prevents duplicate send after retry.

Do not blindly retry a send that may already have reached the provider and been processed.

### 9.3 Logging

Every job execution should log:

- notification type
- business id
- recipient
- provider name
- status
- error details if any
- dedupe key

This gives us traceability without exposing secrets.

---

## 10. Implementation Rules for Phase 1

### Rule 1: Keep the first implementation minimal but extensible

Do not build all future features in one shot. Phase 1 should establish the delivery architecture and the first working notification path.

### Rule 2: Do not hardcode message content inside the service when templates exist

If a template is required, resolve it from the template system, then render variables.

### Rule 3: Keep the queue pattern consistent

If a notification is triggered by a business event, it must be dispatched asynchronously.

### Rule 4: Use a single message logging flow

All provider sends must go through the same log/status tracking path.

### Rule 5: Design for idempotency from day one

Even if the first release only uses a basic dedupe key, the code must be aware of duplicate prevention.

### Rule 6: Preserve existing behavior

The change should not remove current config screens, template records, or demo fallback paths.

---

## 11. Initial Phase 1 Deliverables

By the end of Phase 1, we should have:

1. WhatsApp provider interface and demo provider
2. WhatsApp service orchestration layer
3. Notification payload creation flow
4. queued ProcessWhatsAppNotificationJob
5. dedupe key handling
6. template resolution layer
7. message log status updates
8. optional config validation for sending path

This completes the foundation needed for later subscription, expiry, and inventory notifications.

---

## 12. Example Implementation Flow

### Notification request object

{
"type": "low_stock",
"business_id": 5,
"branch_id": 2,
"recipient_phone": "+254712345678",
"template_key": "inventory.low_stock",
"template_data": {
"business_name": "Sample Shop",
"product_name": "Rice",
"quantity": 4,
"threshold": 5
},
"dedupe_key": "notification:inventory:low-stock:business-5:branch-2:product-19"
}

### Processed by job

- validate config
- resolve template
- render message
- send via provider
- write log

---

## 13. What We Should Not Do

- Do not put inventory threshold logic inside the WhatsApp provider
- Do not send business notifications synchronously from unrelated services
- Do not build provider-specific logic directly into controllers or jobs
- Do not skip dedupe handling for alerts and scheduled jobs
- Do not rely on hardcoded template strings in service methods

---

## 14. Implementation Sequence

1. Create provider contract and demo provider
2. Create WhatsApp notification service and template service
3. Add queue job for processing notification payloads
4. Add dedupe and log tracking
5. Ensure config resolution works for a business
6. Validate successful demo-safe send flow
7. Move to subscription and inventory event wiring after foundation is stable

---

## 15. Success Criteria for Phase 1

The implementation is considered complete when:

- a notification can be created from a business event
- a queued job processes the request
- provider selection is abstracted
- config is resolved per business
- the message log tracks success/failure reliably
- duplicate sends are prevented with dedupe keys
- demo mode still functions as a fallback

---

## 16. Final Note

This is the architecture we will follow during implementation. The goal is not to add every feature at once, but to establish the correct foundation so the later subscription, expiry, report, and inventory features can be added cleanly and safely.
