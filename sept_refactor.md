# WhatsApp Module Refactoring Plan - September 2026

## Audit Overview

Based on comprehensive review of the current WhatsApp module in DuukaFlow, here's the assessment and refactoring plan to address identified gaps and move toward production readiness.

---

## 1. Current Implementation Status

### ✅ **Well-Wired Components**

| Component | Status |
|-----------|--------|
| Event-driven architecture (10 event types + listeners) | ✅ Registered in `WhatsAppEventServiceProvider` |
| Queue pattern (`ProcessWhatsAppNotificationJob`) | ✅ Async delivery via Laravel queue |
| Demo-safe fallback | ✅ All paths default to demo when no paid config |
| Message logging (`WhatsAppMessageLog`) | ✅ Tracks status, provider response, errors |
| Template system (`WhatsAppTemplate` + `WhatsAppTemplateService`) | ✅ Variable rendering with `{{}}` and `{}` syntax |
| Provider abstraction (`WhatsAppProviderInterface`) | ✅ Interface + `DemoWhatsAppProvider` factory |
| UI config screen (`WhatsAppSettings.tsx`) | ✅ Save/test functionality with demo fallback |
| API routes (config, templates, logs, test-endpoints) | ✅ All registered in `routes/whatsapp.php` |

### ⚠️ **Architectural Gaps**

1. **No deduplication** - `ProcessWhatsAppNotificationJob` generates dedupe keys but never checks them before sending. Retries will cause duplicate messages.

2. **No scheduled jobs** - Missing cron jobs for:
   - 2-day subscription expiry reminders
   - Free period expiry notifications
   - Monthly business reports (1st of month)

3. **No recipient management** - No `whatsapp_recipients` table, no preference opt-in/out system, all notifications go to business phone only

4. **Provider factory limited** - `WhatsAppProviderFactory` only supports `demo`; other provider names fall back silently

5. **No idempotency** - Failed job retries can send duplicate messages since there's no dedupe check

6. **Branch scoping** - All listeners hardcode `branch_id: null` despite architecture supporting it

7. **Template initialization** - `WhatsAppService::ensureTemplatesForBusiness` creates defaults on-the-fly

### ❌ **Missing Business Requirements** (from `whatsapp.md`)

| Requirement | Status |
|-------------|--------|
| Business registration welcome | ⚠️ Listener exists, template needs verification |
| New subscription notifications | ✅ `SubscriptionCreatedListener` queues notification |
| Plan change notifications | ✅ `SubscriptionPlanChangedListener` queues notification |
| Renewal notifications | ❌ No listener/handler |
| Payment failure alerts | ✅ `PaymentFailedListener` queues notification |
| Subscription expiry | ✅ `SubscriptionExpiredListener` queues notification |
| **2-day expiry reminders** | ❌ Missing entirely |
| Free period expiry | ❌ Missing entirely |
| Monthly performance reports | ❌ `queueMonthlyReportAlert` exists but no scheduled job |
| Purchase order notifications | ✅ `PurchaseOrderCreatedListener` queues notification |
| Sales order notifications | ✅ `SaleOrderCreatedListener` queues notification |
| Low stock alerts | ✅ `LowStockAlertListener` queues notification |
| Out of stock alerts | ✅ `OutOfStockAlertListener` queues notification |

---

## 2. Refactoring Plan

### **Phase 1: Critical Fixes (Sprint 1)**

#### 1. Add deduplication to `ProcessWhatsAppNotificationJob`
**File:** `api/app/Jobs/ProcessWhatsAppNotificationJob.php`

**Change:** Before sending, query `WhatsAppMessageLog` for existing `dedupe_key`. Skip send if record exists with `sent` status.

```php
// Add at the start of handle() method, after config resolution:
$existingLog = WhatsAppMessageLog::where('business_id', $businessId)
    ->where('dedupe_key', $normalized['dedupe_key'])
    ->first();

if ($existingLog && $existingLog->status === 'sent') {
    Log::info('Skipping duplicate WhatsApp notification', [
        'business_id' => $businessId,
        'dedupe_key' => $normalized['dedupe_key'],
    ]);
    return; // Skip - already sent
}
```

**Also add `dedupe_key` fillable to `WhatsAppMessageLog` model.**

#### 2. Extend `WhatsAppProviderFactory` for multiple providers
**File:** `api/app/Services/WhatsApp/WhatsAppProviderFactory.php`

**Change:** Support configurable providers with default to demo.

```php
public static function create(array $config = ): WhatsAppProviderInterface
{
    $providerName = strtolower((string) ($config['provider'] ?? config('services.whatsapp.provider', 'demo')));

    return match ($providerName) {
        'demo' => new DemoWhatsAppProvider($config),
        'meta' => new MetaWhatsAppProvider($config), // future: will implement
        default => new DemoWhatsAppProvider($config),
    };
}
```

#### 3. Add `dedupe_key` column to `WhatsAppMessageLog` migration + model
**Change:** Add `$fillable` and `$casts` for `dedupe_key` in `WhatsAppMessageLog.php`.

---

### **Phase 2: Missing Business Requirements (Sprint 2-3)**

#### 4. Create `SendSubscriptionExpiryNotificationsJob`
**New file:** `api/app/Jobs/SendSubscriptionExpiryNotificationsJob.php`

**Purpose:** Send 2-day expiry reminders + immediate expiry alerts.

**Schedule:** `*/2 * * * *` (every 2 minutes for testing, or `0 0 * * *` daily in production)

**Logic:**
- Query businesses with subscriptions ending within 2 days
- Check recipient preferences to avoid spamming
- Queue `ProcessWhatsAppNotificationJob` for each business
- Track sent status to prevent duplicates

#### 5. Create `SendFreePeriodExpiryJob`
**New file:** `api/app/Jobs/SendFreePeriodExpiryJob.php`

**Purpose:** Notify businesses when free trial period ends.

**Schedule:** `0 9 * * *` (9 AM daily)

**Logic:**
- Find newly registered businesses whose free period has passed
- Send expiry notification with upgrade prompt
- Respect notification preferences

#### 6. Create `GenerateMonthlyBusinessReportJob`
**New file:** `api/app/Jobs/GenerateMonthlyBusinessReportJob.php`

**Purpose:** Monthly performance summary via WhatsApp.

**Schedule:** `0 1 * * *` (1st of month at 1 AM)

**Logic:**
- Consume existing analytics services (revenue, purchases, profit, sales counts)
- Respect business timezone
- Generate configurable date range (previous completed month)
- Send to business owner/configured recipients
- Respect notification preferences

#### 7. Add missing event listeners
**File:** `api/app/Events/WhatsAppNotificationEvents.php` + `api/app/Providers/WhatsAppEventServiceProvider.php`

**Add:**
- `SubscriptionRenewed` event + listener
- `PaymentFailed` already exists ✅
- Consider `FreePeriodExpired` event

---

### **Phase 3: Observability & Reliability (Sprint 3-4)**

#### 8. Enhance error handling in `ProcessWhatsAppNotificationJob`
**File:** `api/app/Jobs/ProcessWhatsAppNotificationJob.php`

**Changes:**
- Categorize errors: `invalid_number`, `rate_limit`, `provider_down`, `unknown`
- Implement retry logic with exponential backoff
- Track `provider_message_id` from provider response
- Add error classification in log

```php
// Example error categorization:
$errorCategory = match (true) {
    str_contains($result['error'] ?? '', 'timeout') => 'timeout',
    str_contains($result['error'] ?? '', 'rate') => 'rate_limit',
    str_contains($result['error'] ?? '', 'invalid') => 'invalid_number',
    default => 'unknown',
};

// Store in log:
'error_code' => $errorCategory,
```

#### 9. Add notification preferences system
**New files:**
- `whatsapp_recipients` table migration
- `WhatsAppRecipient` model
- Preference settings in UI (`WhatsAppSettings.tsx`)
- Business notification preference model

**Logic:** Businesses can opt-in/out of categories:
- ☑ Subscription notifications
- ☑ Inventory alerts
- ☑ Order notifications
- ☑ Monthly reports
- ☑ System/account notifications

#### 10. Update listeners for branch scoping
**Files:** All listeners in `api/app/Listeners/WhatsApp/`

**Change:** Respect branch-level recipients where applicable:
- Low stock → relevant branch recipient
- Purchase/sale orders → business owner + relevant branch
- Use `branch_id` from event data when available

---

### **Phase 4: Database & Infrastructure (Sprint 4)**

#### 11. Required migrations
1. **`whatsapp_recipients` table** - Store WhatsApp numbers per business/branch
2. **Enhance `whats_app_message_logs`** - Add `dedupe_key`, `provider_message_id`, `error_code`
3. **`notification_preferences` table** - Business/branch preference flags

#### 12. Scheduled task registration
**File:** `api/app/Console.php` or dedicated scheduler file

```php
protected function schedule($schedule)
{
    $schedule->job(new SendSubscriptionExpiryNotificationsJob)->everySixHours()->withoutOverlapping();
    $schedule->job(new SendFreePeriodExpiryJob)->dailyAt(9, 0);
    $schedule->job(new GenerateMonthlyBusinessReportJob)->monthlyOn(1, 0);
}
```

---

### **Phase 5: Testing (Sprint 5)**

#### 13. Unit tests required
- `WhatsAppService` - Configuration and template management
- `NotificationQueueService` - Message queuing and deduplication
- `ProviderAbstraction` - Provider integration patterns
- `TemplateEngine` - Message parameterization

#### 14. Integration tests
- Notification flow: Business event → WhatsApp notification pipeline
- Queue processing: Background job execution with dedupe check
- Error handling: API failures and retry tracking

#### 15. End-to-end tests
- Registration notification: Full registration → WhatsApp flow
- Subscription events: All lifecycle notifications
- Expiry reminders: 2-day reminder system functionality
- Monthly reports: Report generation and delivery
- Inventory alerts: Stock threshold notifications
- Deduplication: Duplicate prevention mechanisms

---

### **Phase 6: Cleanup & Optimization**

#### 16. Remove deprecated code
- Remove `SendWhatsAppNotificationJob` if no longer needed (replace with `ProcessWhatsAppNotificationJob`)
- Clean up any hardcoded template strings

#### 17. Performance optimizations
- Index `dedupe_key` on `whats_app_message_logs` table
- Cache config resolution for frequently accessed businesses
- Batch processing for bulk notifications

#### 18. Documentation updates
- Update `whatsapp.md` with actual implementation status
- Document new job/scheduler additions
- Update UI components with new preference settings

---

## 3. Priority Order

1. **Critical:** Deduplication in `ProcessWhatsAppNotificationJob` (prevents duplicate messages on retry)
2. **Critical:** Extend `WhatsAppProviderFactory` for multiple providers
3. **High:** Add `dedupe_key` to message log model + migration
4. **High:** Create scheduled jobs (expiry reminders, monthly reports)
5. **Medium:** Error categorization + retry logic in job
6. **Medium:** Notification preferences system
7. **Low:** Branch scoping refinements
8. **Low:** Cleanup and optimization

---

## 4. Success Criteria

After refactoring, the implementation should:

- ✅ Prevent duplicate notifications from job retries (dedupe key check)
- ✅ Support multiple WhatsApp providers (config-driven, demo default)
- ✅ Queue-based async delivery (no synchronous API calls in request flow)
- ✅ Scheduled jobs for expiry reminders (2-day) and monthly reports
- ✅ Configurable notification preferences per business
- ✅ Branch-level recipient support where applicable
- ✅ Enhanced error handling with categorization and tracking
- ✅ Message logs track `provider_message_id`, `error_code`, `dedupe_key`
- ✅ All business requirements from `whatsapp.md` addressed or have clear implementation path

---

## 5. Implementation Sequence

| Step | Priority | Estimated Effort |
|------|----------|------------------|
| 1. Add deduplication to job | 1 - Critical | 2-3 hours |
| 2. Extend provider factory | 1 - Critical | 1-2 hours |
| 3. Add dedupe_key to message log | 2 - High | 1 hour |
| 4. Create SendSubscriptionExpiryNotificationsJob | 3 - High | 3-4 hours |
| 5. Create SendFreePeriodExpiryJob | 3 - High | 2-3 hours |
| 6. Create GenerateMonthlyBusinessReportJob | 3 - High | 3-4 hours |
| 7. Enhance error handling | 4 - Medium | 2-3 hours |
| 8. Add notification preferences | 4 - Medium | 4-5 hours |
| 9. Branch scoping updates | 5 - Low | 2-3 hours |
| 10. Testing & cleanup | 5 - Low | 5-8 hours |

**Total estimated effort: ~30-40 hours**

---

## 6. Key Principles (from `whatsapp_implementation.md`)

- ✅ **Keep first implementation minimal but extensible**
- ✅ **Do not hardcode message content inside service when templates exist**
- ✅ **Keep the queue pattern consistent** - all notifications async
- ✅ **Use a single message logging flow** - all sends go through same path
- ✅ **Design for idempotency from day one** - dedupe keys prevent duplicates
- ✅ **Preserve existing behavior** - demo mode remains functional fallback

---

*Refactoring plan initiated: September 2026*
*Goal: Production-ready, queue-friendly WhatsApp notification architecture*