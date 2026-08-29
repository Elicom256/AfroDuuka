# WhatsApp Module Audit & Refactoring Plan

## Audit Summary

Based on a comprehensive review of the current WhatsApp module in DuukaFlow, here's an assessment of what exists and what needs to be implemented.

## 1. Current Implementation

### Existing Components:

#### Models:
- **`WhatsAppConfig`** (`api/app/Models/WhatsAppConfig.php`): Stores WhatsApp provider configuration per business
- **`WhatsAppTemplate`** (`api/app/Models/WhatsAppTemplate.php`): Message templates with parameters
- **`WhatsAppMessageLog`** (`api/app/Models/WhatsAppMessageLog.php`): Message delivery logs

#### Services:
- **`WhatsAppService`** (`api/app/Services/WhatsApp/WhatsAppService.php`): 
  - Manages business WhatsApp config resolution
  - Provides demo-safe message queueing/sending
  - Ensures default templates for new businesses

#### Jobs:
- **`SendWhatsAppNotificationJob`** (`api/app/Jobs/SendWhatsAppNotificationJob.php`): Simple queue-based WhatsApp sender (currently demo-only)
- **`CheckNotificationsJob`** (`api/app/Jobs/CheckNotificationsJob.php`): Checks for low stock and overdue payments

#### Controllers:
- **`WhatsAppConfigController`** (`api/app/Http/Controllers/WhatsAppConfigController.php`): API endpoints for WhatsApp settings

#### Database Migrations:
- **`2026_08_21_000001_create_whatsapp_configs_table.php`**: Creates WhatsApp configs, templates, and message logs tables

#### UI:
- **`WhatsAppSettings`** (`ui/src/app/pages/dashboards/admin/components/settings/WhatsAppSettings.tsx`): Business-facing WhatsApp configuration panel

## 2. Requirement Coverage

| Requirement | Current Status | Existing Implementation | Missing/Problem |
|-------------|---------------|------------------------|----------------|
| Registration | ❌ Missing | None | No welcome/registration notification |
| Subscription | ❌ Missing | None | No new subscription notifications |
| Plan Change | ❌ Missing | None | No plan change notifications |
| Renewal | ❌ Missing | None | No renewal notifications |
| Payment Failure | ❌ Missing | None | No payment failure notifications |
| Subscription Expiry | ⚠️ Partially implemented | Basic expiry checks in CheckNotificationsJob | No dedicated WhatsApp notifications |
| Expiry Reminders | ❌ Missing | None | No 2-day reminder system |
| Free Period Expiry | ❌ Missing | None | No free period expiry notifications |
| Monthly Report | ❌ Missing | None | No monthly performance reports |
| Purchase Orders | ❌ Missing | None | No purchase order notifications |
| Sales Orders | ❌ Missing | None | No sales order notifications |
| Low Stock | ⚠️ Partially implemented | Low stock detection in CheckNotificationsJob + demo template | No WhatsApp integration |
| Out of Stock | ❌ Missing | None | No out-of-stock notifications |

## 3. Architectural Problems

### 🔧 Tight Coupling
- **Business logic mixed with WhatsApp delivery**: Currently, business notifications are hardcoded into the WhatsApp service
- **No proper abstraction**: WhatsApp provider integration lacks a clean service/interface layer
- **Templates scattered**: Message templates are hardcoded in `WhatsAppService.php` rather than being reusable

### 📋 Poor Separation of Concerns
- **WhatsApp logic embedded in business modules**: Low stock detection is in `CheckNotificationsJob`, not WhatsApp-specific
- **Notification routing mixed**: All notifications go through a generic `NotificationService` without WhatsApp-specific handling
- **Recipient logic scattered**: No centralized recipient management

### 🔄 Duplicate/Weak Abstractions
- **Missing provider abstraction**: No interface for different WhatsApp providers (Meta, 360dialog, etc.)
- **No notification architecture**: Lacks the pattern: Business Event → Notification Decision → Queue → WhatsApp Service → Provider
- **No idempotency**: Missing mechanisms to prevent duplicate notifications from retries

### 📈 Scalability/Issues
- **Demo-only architecture**: All WhatsApp sending is in demo mode with hardcoded values
- **No scheduled jobs**: Missing cron-based jobs for subscription expiry, free period, monthly reports
- **Poor error handling**: Basic error logging without comprehensive failure tracking
- **No recipient preferences**: No way for businesses to control which notifications they receive

### 💾 Database Issues
- **Limited recipient storage**: No recipient preferences or opt-out functionality
- **No branch scoping**: All notifications target the same business level
- **No deduplication fields**: Missing tracking to prevent duplicate messages

## 4. Missing Features

### Critical Business Requirements
1. **Business Registration Notification** - Welcome message upon successful registration
2. **Subscription Event Notifications** - New subscriptions, plan changes, renewals, payment failures
3. **Subscription Expiry & Reminders** - Immediate expiry notifications + 2-day reminder system
4. **Free Period Expiry** - Notification when free period ends
5. **Monthly Business Performance Reports** - Comprehensive WhatsApp-based summary
6. **Order Notifications** - Purchase and sales order alerts
7. **Comprehensive Inventory Alerts** - Low stock AND out-of-stock with state-based triggering

### Infrastructure
1. **Provider Abstraction Layer** - Interface for WhatsApp providers
2. **Message Queue System** - Background job processing for all WhatsApp notifications
3. **Scheduled Job Infrastructure** - Cron jobs for recurring notifications
4. **Recipient Management** - Business/branch-level recipient selection
5. **Notification Preferences** - Configurable notification categories
6. **Deduplication System** - Prevent duplicate notifications
7. **Advanced Logging** - Detailed notification status tracking

## 5. Refactoring Plan

### Phase 1: Core Architecture Foundation (Weeks 1-2)

#### **Priority 1: Notification Architecture**
1. **Create `WhatsAppNotification` Event**
   - Event-based architecture: Business Event → WhatsAppNotification → Job → Service → Provider
2. **Implement `WhatsAppMessageQueue` Service**
   - Central message queue with idempotency support
   - Job deduplication mechanisms
3. **Add Provider Abstraction**
   - Interface for WhatsApp providers
   - Implement demo provider as baseline

#### **Priority 2: Message Templates & Management**
1. **Centralized Template System**
   - Move templates from `WhatsAppService` to database
   - Template-based, parameterized messages
   - Multi-language support
2. **Template Categories**
   - Registration, Subscription, Renewal, Expiry, Report, Orders, Inventory

### Phase 2: Business Event Integration (Weeks 3-4)

#### **Subscription Management**
1. **Create `WhatsAppSubscriptionEvents` Service**
   - New subscription notifications
   - Plan change notifications
   - Renewal confirmations
   - Payment failure alerts
2. **Integrate with Subscription Module**
   - Add WhatsApp notification triggers
   - Ensure async processing

#### **Business Registration**
1. **Implement `WhatsAppBusinessRegistration`**
   - Async welcome message after registration
   - Business identification and personalization

### Phase 3: Scheduled & Recurring Notifications (Weeks 5-6)

#### **Subscription Lifecycle**
1. **Subscription Expiry Notifications**
   - Immediate expiry alert
   - 2-day reminder system
   - Reactivation prompts
2. **Free Period Management**
   - Automated free period expiry notification
   - Upgrade prompts with plan information

#### **Monthly Reports**
1. **Create `WhatsAppMonthlyReport` Job**
   - Scheduled to run on 1st of each month
   - Consume existing analytics services
   - Configurable date ranges and formatting

### Phase 4: Inventory Management (Weeks 7-8)

#### **State-Based Alerts**
1. **Implement `WhatsAppInventoryAlerts`**
   - Low stock triggers (state-based)
   - Out-of-stock notifications
   - Alert reset mechanisms
   - Branch-level scoping

#### **Recipient System**
1. **Add `WhatsAppRecipient` Model**
   - Business/branch-level recipients
   - Phone number normalization
   - Opt-out preferences

### Phase 5: Reliability & Observability (Weeks 9-10)

#### **Error Handling & Monitoring**
1. **Enhanced Logging**
   - Detailed notification logs with all required fields
   - Provider message IDs tracking
   - Error categorization and retry tracking
2. **Deduplication System**
   - Job-level deduplication
   - Message-level deduplication
   - Replay protection for retries

#### **Configuration & Preferences**
1. **Notification Preferences**
   - Business-configurable notification categories
   - Branch-level preferences
   - Template customization

### Phase 6: Integration & Testing (Week 11)

#### **System Integration**
1. **Update Business Modules**
   - Add WhatsApp notification triggers
   - Ensure proper async processing
2. **Update UI**
   - Enhanced WhatsApp settings
   - Recipient management interface
3. **Comprehensive Testing**
   - Unit tests for all services
   - Integration tests for notification flow
   - End-to-end tests for critical paths

## 6. Database Changes

### Required Migrations:
1. **Add `notification_preferences` table**
   - Business/branch notification preferences
   - Opt-in/opt-out flags per category

2. **Add `message_deduplication` table**
   - Track sent messages to prevent duplicates
   - Hash-based deduplication tracking

3. **Enhance `whats_app_message_logs`**
   - Add provider message ID
   - Add more detailed error tracking
   - Add recipient preference tracking

4. **Add `whatsapp_recipients` table**
   - Store WhatsApp numbers per business/branch
   - Support multiple recipients per business

## 7. Jobs, Events & Scheduled Tasks

### Proposed Jobs:
1. **`ProcessWhatsAppNotificationJob`**
   - Core WhatsApp delivery job
   - Idempotent retry handling

2. **`SendSubscriptionExpiryNotificationsJob`**
   - Subscription expiry alerts
   - 2-day reminder system

3. **`SendFreePeriodExpiryJob`**
   - Free period expiry notification
   - Upgrade prompts

4. **`GenerateMonthlyBusinessReportJob`**
   - Monthly performance reports
   - Configurable sending schedule

5. **`CheckInventoryAlertsJob`**
   - Low stock and out-of-stock detection
   - State-based triggering

### Proposed Events:
- `BusinessRegistered`
- `SubscriptionCreated`
- `SubscriptionPlanChanged`
- `SubscriptionRenewed`
- `PaymentFailed`
- `SubscriptionExpired`
- `LowStockAlert`
- `OutOfStockAlert`

### Proposed Scheduled Tasks:
- `*/2 * * * *` - Subscription expiry reminders
- `0 1 * * *` - Monthly business reports
- `0 */6 * * *` - Inventory checks (every 6 hours)

## 8. Testing Plan

### Unit Tests:
- **WhatsAppService** - Configuration and template management
- **NotificationQueueService** - Message queuing and deduplication
- **ProviderAbstraction** - Provider integration patterns
- **TemplateEngine** - Message parameterization

### Integration Tests:
- **Notification Flow** - Business event → WhatsApp notification pipeline
- **Queue Processing** - Background job execution
- **Scheduled Jobs** - Cron-based notifications
- **Error Handling** - API failures and retries

### End-to-End Tests:
- **Registration Notification** - Full registration → WhatsApp flow
- **Subscription Events** - All subscription lifecycle notifications
- **Expiry Reminders** - Reminder system functionality
- **Monthly Reports** - Report generation and delivery
- **Inventory Alerts** - Stock threshold notifications
- **Deduplication** - Duplicate prevention mechanisms

## Implementation Rules

### Development Guidelines:
1. **Reuse Existing Infrastructure**
   - Use existing analytics for reports
   - Reuse notification service where appropriate
   - Follow Laravel conventions

2. **Maintain Existing Functionality**
   - Preserve demo mode as fallback
   - Don't break existing low stock alerts
   - Keep API endpoints backward compatible

3. **Performance & Reliability**
   - Ensure queued jobs are safe to retry
   - Design scheduled tasks for repeated execution
   - Keep database queries efficient
   - Implement proper error handling and logging

4. **Security**
   - Never expose API credentials in logs
   - Secure token storage and transmission
   - Validate phone numbers and recipients

## Next Steps

**Immediate Actions (This Sprint):**
1. Complete this audit report
2. Design the `WhatsAppNotification` event and queue system
3. Create provider abstraction interface
4. Set up centralized template system

**Goal:** Establish a production-ready, queue-friendly WhatsApp notification architecture that supports all business requirements while maintaining the existing demo functionality as a fallback.

---
*Audit completed: August 28, 2026*
*Next phase: Begin Phase 1 implementation*
