# Feature #2 — Customer Communication: Email (Amazon SES) + WhatsApp (Meta Cloud API)

**Source docs:** `industry-features.md` §2 (roadmap P0, `refactor.md` §4 `C3`) ·
`whatsapp.md` (requirements + audit brief) · `whatsapp_plan.md` (audit, Aug 2026) ·
`whatsapp_implementation.md` (Phase 1 blueprint) · `refactor.md` §3.3 (`dedupe_key`)

**Status:** Stages 0–1 and Stage 2 chunks 1–2 are built and tested. This document is the
spec; **[`undone.md`](undone.md) is the working checklist and is where to resume** — it
tracks what is left, marks finished stages, and links the reasoning in `to-check.md`.

**North star:** *"most relevant and meaningful notifications."* Every row in the
catalogue below had to justify its own existence. Volume is the enemy here, not
omission — see [§3 The Meta constraint](#3-the-meta-constraint-why-this-design-is-forced).

---

## 1. Verified current state

I read the code rather than the docs, because the docs are out of date. The
Phase 1 foundation in `whatsapp_implementation.md` **was built** — but a lot of it
is dead or wired wrong.

### 1.1 What genuinely works

| Piece | Location | Note |
|---|---|---|
| `WhatsAppProviderInterface` | `app/Services/WhatsApp/Contracts/WhatsAppProviderInterface.php:5` | 3 methods. Clean seam. |
| `DemoWhatsAppProvider` | `app/Services/WhatsApp/Providers/DemoWhatsAppProvider.php:7` | Returns success, logs. Good fallback. |
| `MetaWhatsAppProvider` | `app/Services/WhatsApp/Providers/MetaWhatsAppProvider.php:7` | **Stub.** Makes no HTTP call. |
| `WhatsAppProviderFactory` | `app/Services/WhatsApp/Providers/../WhatsAppProviderFactory.php:11` | `match` on provider, silent `default` → Demo. |
| `WhatsAppNotificationService` | `app/Services/WhatsApp/WhatsAppNotificationService.php:10` | `buildPayload()` + 11 typed wrappers. Dedupe key shape is good. |
| `ProcessWhatsAppNotificationJob` | `app/Jobs/ProcessWhatsAppNotificationJob.php:18` | The one live pipeline: dedupe → config → template → provider → log. |
| Models | `WhatsAppConfig`, `WhatsAppTemplate`, `WhatsAppMessageLog` | Reusable, but see 1.3. |
| In-app notifications | `app/Models/Notification.php`, `app/Services/NotificationService.php` | Fully working, **DB-row only, zero external channels.** |
| Live schedule | `routes/console.php:13-18` | Two jobs registered. |

### 1.2 What is broken (blocks the feature)

| # | Defect | Location | Impact |
|---|---|---|---|
| **B1** | `event(new BusinessRegistered($business))` with **no import** → resolves to `App\Services\BusinessRegistered` | `app/Services/BusinessService.php:31` | **Fatal `Class not found`. Business creation is 100% broken today.** This also blocks any registration notification and the Quotations demo. |
| **B2** | `WhatsAppEventServiceProvider` not in `bootstrap/providers.php` (only `AppServiceProvider`); and its `$listen` array uses `WhatsAppNotificationEvents\X::class` **without importing the namespace** | `bootstrap/providers.php`, `app/Providers/WhatsAppEventServiceProvider.php:20-49` | **All 10 events + 10 listeners are dead code.** Only 2 direct service calls actually produce traffic (`BusinessService.php:60`, `SubscriptionController.php:33`). |
| **B3** | `GenerateMonthlyBusinessReportJob` uses `WhatsAppTemplate` unimported | `app/Jobs/GenerateMonthlyBusinessReportJob.php:73` | Fatal. Job is also unscheduled. |
| **B4** | Queries `businesses.subscription_ends_at` / `businesses.free_ends_at` — **columns do not exist**; also `whereDoesntHave('whatsappMessageLogs')` (no such relation) and filters `whats_app_message_logs.type` (no such column) | `SendSubscriptionExpiryNotificationsJob.php:44,72` · `SendFreePeriodExpiryJob.php:43,45` | `QueryException`. All three lifecycle jobs dead. |
| **B5** | Live one: `ProcessSubscriptionLifecycleWhatsAppJob` uses `Subscription::status`/`ends_at`/`trial_ends_at` — those **do** exist. But it queries `status != 'active' OR NULL` and then treats `ends_at` as the gate, so it re-evaluates every historical subscription every 6h. | `app/Jobs/ProcessSubscriptionLifecycleWhatsAppJob.php:26-31` | Works by accident; wasteful; scope it. |
| **B6** | Template lookup matches `where('category', $templateKey)` where `$templateKey` is `inventory.low_stock` but seeded categories are `low_stock` / `daily_summary` / `payment` | `ProcessWhatsAppNotificationJob.php:77` | **Never matches.** Every message falls through to `$config->message_template`. Templates are decorative today. |
| **B7** | `whats_app_configs.business_id` has **no unique index**, while `store()` does `updateOrCreate(['business_id' => ...])` | `2026_08_21_000001_create_whatsapp_configs_table.php:12` | Race → duplicate configs → `->first()` becomes non-deterministic. |
| **B8** | `access_token` stored as plaintext `text`; `WhatsAppConfigController::index()` returns the **whole model** incl. `access_token` + `webhook_verify_token` to any authenticated user; `update()` has **no ownership check** | `WhatsAppConfigController.php:26,55` | Secret leak + cross-tenant write. |
| **B9** | Two divergent send paths: `queueDemoMessage()` (async, returns `queued:true`) vs `sendDemoMessage()` (sync, returns `log_id`) | `WhatsAppService.php:91,120` | Only the async one is wired. Sync path is a duplicate to delete. |
| **B10** | Both providers' `validateConfiguration()` use **OR** logic, so a config with only a phone number "validates" | `DemoWhatsAppProvider.php:23`, `MetaWhatsAppProvider.php:23` | Ships broken creds, fails at send time. |
| **B11** | `app/Console/Kernel.php` is **dead** (Laravel 11+ removed the Console Kernel; `bootstrap/app.php` never references it) yet holds the only `GenerateMonthlyBusinessReportJob` schedule | `app/Console/Kernel.php:33` | Monthly report has never run. |
| **B12** | `Schedule::command("queue:work")->everyMinute()` | `routes/console.php:18` | Long-running worker under `schedule:run`. Anti-pattern. |
| **B13** | `BaseModel` applies business/branch global scopes **only when `Auth::check()`** | `app/Models/BaseModel.php:27,37` | **Queued and scheduled jobs run unscoped** — tenant-isolation hole. |
| **B14** | `App\Models\Notification` **shadows** `Illuminate\Notifications\Notification` | `app/Models/Notification.php:11` | Any file importing both cannot use Laravel Notifications. |
| **B15** | `dedupe_key` is a plain `->index()`, not unique; check-then-send in the job | `2026_08_21_000001...:54`, `ProcessWhatsAppNotificationJob.php:36-50` | Racy under concurrent workers. |

### 1.3 What does not exist at all

- **No email whatsoever.** No `app/Mail/`, zero `Mail::` calls, zero Mailables.
  `app/Notifications/LowStockNotification.php` is untouched scaffold, never invoked.
- **`aws/aws-sdk-php` is not in `composer.json` and not in `vendor/`.** The `ses`
  mailer cannot work until `composer require aws/aws-sdk-php`.
- No `notification_preferences` table/model. No `notification_recipients` table.
- No webhook endpoint — `webhook_verify_token` and `last_webhook_at` columns exist
  with nothing reading or writing them. So **no delivery-status tracking**: we would
  never learn that a message failed.
- No `NotificationPreference`, no unsubscribe, no timezone, no template approval sync.
- `Business` has no `timezone`. `BusinessBranch` **does** have `phone` (good — branch
  recipients are viable). `Business` has `name`/`email`/`phone`.

### 1.4 Requirement coverage (from `whatsapp.md`)

| Requirement | Status | Reality |
|---|---|---|
| Registration | ⚠️ | Code exists at `BusinessService.php:60` — unreachable because of **B1** |
| Subscription | ⚠️ | `SubscriptionController.php:33` — sole working path |
| Plan Change | ❌ | Service method exists, nothing calls it |
| Renewal | ❌ | Service method exists, nothing calls it |
| Payment Failure | ❌ | Service method exists, nothing calls it |
| Subscription Expiry | ⚠️ | Live job, but subscription selection is sloppy (**B5**) |
| Expiry Reminders (2-day) | ⚠️ | Live job — `days % 2` bucketing is actually correct |
| Free Period Expiry | ❌ | **B4** |
| Monthly Report | ❌ | **B3** + **B11** — never run |
| Purchase Orders | ❌ | Service method exists, nothing calls it |
| Sales Orders | ❌ | Service method exists, nothing calls it |
| Low Stock | ⚠️ | `CheckNotificationsJob` has a 24h re-notify guard but writes **in-app only**, never WhatsApp |
| Out of Stock | ❌ | Service method exists, nothing calls it |

**Summary:** the plumbing is ~40% built and ~60% of it is unreachable. The right move
is **repair + finish**, not rewrite. That also keeps `whatsapp_implementation.md`'s
"preserve existing behaviour" rule intact.

---

## 2. Channel strategy — what goes where

The two channels are not interchangeable. Splitting them is the single biggest lever
on "relevant and meaningful":

| | **WhatsApp** | **Email (SES)** |
|---|---|---|
| Use for | Short, urgent, actionable, **state-change** alerts | Rich, **document-shaped** communication |
| Carries | ≤ ~4 lines, a number, an action | PDF receipts, monthly report, full history |
| Template | Meta pre-approved, mandatory | Blade Mailable, free-form |
| Read rate | ~95% | ~25% |
| Cost | Per-conversation (Meta bills templates) | $0.10/1k |
| Failure mode | Quality-score collapse if spammy | Silent inbox ignore |

**Rules I'll hold to:**
1. If it needs an attachment or a table → **email**.
2. If it is a state change the owner must act on within 24h → **WhatsApp**.
3. If it is a *summary* → **email**, with WhatsApp getting only a one-line nudge
   ("your August report is ready") — never the numbers.
4. If a customer (not the owner) is the audience → **email by default**, WhatsApp only
   if the customer gave a number and the business is in a service window. Out of MVP.
5. Never both with identical content. Escalate, don't duplicate.

---

## 3. The Meta constraint (why this design is forced)

This is the part that dictates everything, and it is **not** in any of the docs.

**A 24-hour customer service window opens when *the customer* messages you.** Inside
it you may send free-form text. Outside it, **every message must be a pre-approved
template**.

DuukaFlow's notifications are 100% business-initiated. Therefore:

> **All outbound WhatsApp from DuukaFlow must be Meta template messages.**
> There is no free-text path in production.

Consequences:

- **Template-first, not template-optional.** Every catalogue row needs a Meta template
  created and approved in WhatsApp Manager *before* it can ship. This is an
  operational dependency and a lead-time risk, not a code task.
- **The DB template is a different object from the Meta template.** This is the
  mistake the current schema invites (`whats_app_templates` conflates them). See §5.3.
- **Category matters.** Most of these are `UTILITY` (transactional-ish, cheap,
  generous limits). Monthly-report nudge could be `MARKETING` — which is **billed
  separately and has much lower quality tolerance**. Recommendation: mark it
  `UTILITY` to stay in the cheap tier. Do not send `MARKETING` in MVP at all.
- **`message_status: accepted` ≠ delivered.** A 200 from the Messages API only means
  Meta queued it. Delivery/read/failure arrive **on the webhook**. So **the webhook is
  not optional observability — it is the delivery contract.** Without it we have no
  idea if anything worked, and `failed` messages are invisible.
- **Quality score is the real risk.** Meta's messaging tiers throttle you at 80/50/5
  failures per 1000 conversations, and templates get paused/archived. A monthly report
  sent to 500 businesses that nobody reads destroys the account. Hence the throttle
  rails in `.env.prod` and the per-type cooldowns in §5.5.
- **Parameter format matters.** `NAMED` vs `POSITIONAL` and the number of `{{n}}`
  placeholders must match the approved template exactly, or sends fail with a
  template-parameter mismatch. We store the expected variable order per template.

Meta reference facts (verified against current docs):
- Endpoint: `POST https://graph.facebook.com/{version}/{phone-number-id}/messages`
- Auth: `Authorization: Bearer <system-user-token>` (long-lived, not a user token)
- Body: `messaging_product=whatsapp`, `recipient_type=individual`, `to`, `type`, and a
  matching object (`text.body`, or `template.{name,language.code,components}`)
- Current Graph version: **v26.0** — pinned in `.env.prod`, bumped deliberately
- Success response carries `messages[0].id` → our `provider_message_id`
- TTL: delivery retried for 30 days (10 min for auth templates)

---

## 4. Target architecture

```
Business event (after commit)
        │
        ▼
NotificationDispatcher            ← the ONLY entry point for business modules
  ├─ resolve recipients  (channel, scope, opt-in, verified)
  ├─ resolve preferences (mandatory vs preference-checked)
  ├─ render template     (in-app wording → Meta {{n}} params)
  └─ reserve dedupe      (INSERT notification_deliveries, UNIQUE(dedupe_key))
        │                        ↑ duplicate here = silently dropped
        ├──► SendWhatsAppNotificationJob  ─► MetaWhatsAppProvider  ─► graph.facebook.com
        │                                                          └─► DemoWhatsAppProvider
        └──► SendEmailNotificationJob     ─► SesMailChannel         ─► email-smtp.*.amazonaws.com
                                                                   └─► LogMailChannel
        │
        ▼
notification_deliveries  (status: pending→sending→sent→delivered|read|failed)
        ▲                    ▲
        │                    └── POST /api/webhooks/whatsapp   (status reconciliation)
        │                    └── POST /api/webhooks/ses        (bounce/complaint)
        └─ the single place to answer "did they get it?"
```

**Invariants:**
- Business modules never import a provider, a Mailable, or a template string.
- A notification is created **after** the source transaction commits (via
  `DB::afterCommit` / queued listener), never before.
- Every row in `notification_deliveries` has a `dedupe_key`; the unique index is the
  dedupe mechanism, not an application-level `SELECT`.
- Retries are gated on the row's own state, so a retry after an ambiguous timeout
  cannot double-send if the webhook already confirmed delivery.

---

## 5. Design decisions

### 5.1 `notification_deliveries` — one cross-channel log

`whats_app_message_logs` is channel-shaped (`template_id`, `read_at`, no `type`).
Email and WhatsApp have different status vocabularies. Rather than bolt email onto it,
add one table that serves both and make the old one a deprecated read-only mirror.

| Column | Why |
|---|---|
| `business_id`, `branch_id` (nullable) | tenant + branch scope on every row |
| `channel` | `whatsapp` \| `email` |
| `category` | the preference bucket (`subscription`, `inventory`, `order`, `report`, `system`) |
| `type` | specific event (`subscription.renewed`) — **fixes the B4 missing-column class of bug** |
| `template_key` | e.g. `subscription.renewed` |
| `recipient_id` → `notification_recipients` | who |
| `recipient_address` | denormalised snapshot (numbers/emails change) |
| `status` | `pending`/`sending`/`sent`/`delivered`/`read`/`failed`/`suppressed` |
| `provider`, `provider_message_id` | indexed; webhook joins on this |
| `attempt_count`, `last_attempt_at` | retry budget |
| `payload` json | rendered params — replay/debug without re-rendering |
| `error_code`, `error_message` | triage |
| `sent_at`, `delivered_at`, `read_at`, `failed_at`, `suppressed_at` | timeline |
| **`dedupe_key`** | **`->unique()`** |
| `meta_template_name`, `meta_template_language` | so a failed send is diagnosable |
| `is_mandatory` | transactional — bypasses preferences, not suppressible |

Indexes: `unique(dedupe_key)`, `(provider, provider_message_id)`, `(business_id, created_at)`,
`(channel, status)`.

Keep `whats_app_message_logs` for one release as a read-only view (or a
`notification_delivery_id` pointer) so nothing breaks, then drop it.

### 5.2 Dedupe: reserve, then send

Current flow is check-then-send (**B15**). New flow:

1. Dispatcher `INSERT`s the row with `status=pending` and the deterministic `dedupe_key`.
2. Unique violation → already reserved → **return quietly**. This is now race-proof.
3. Job flips `pending → sending`, calls the provider, then `sending → sent`.
4. Ambiguous failure (timeout after the request left): do **not** auto-retry. Mark
   `sending` and let the webhook resolve it. Only retry on a definitive pre-flight
   rejection (4xx validation, 429 with no `Retry-After` ambiguity).
5. `attempt_count` vs `WHATSAPP_MAX_ATTEMPTS` caps the rest, with
   `WHATSAPP_RETRY_BACKOFF=[30,300,1800]`.

Dedupe key shapes (deterministic, no timestamps inside):

```
registration:welcome:business-7
subscription:created:subscription-3:payment-11
subscription:expired:subscription-3:2026-09-30        ← the ends_at, so a new period re-fires
subscription:reminder:subscription-3:bucket-7        ← floor(days_since_expiry/2)
trial:ended:subscription-3:2026-08-14                 ← the trial_ends_at
report:monthly:business-7:2026-08
inventory:low_stock:business-7:branch-2:product-19:episode-4
inventory:out_of_stock:business-7:branch-2:product-19:episode-4
order:purchase:business-7:branch-2:po-88
order:sale:business-7:branch-2:so-91
quotation:sent:quotation-14:v2                        ← PDF version, so a re-send is allowed
sale:receipt:sale-233
```

The `episode-N` counter is what makes stock alerts state-based instead of
threshold-based (**§5.5**).

### 5.3 Templates: two layers, explicitly separated

Current `whats_app_templates` conflates "the wording we render" with "the template
Meta has approved". Split the responsibilities, keep one table:

| Column | Purpose |
|---|---|
| `name`, `category`, `locale`, `body`, `variables` | **existing** — our wording, `{{token}}` substitution |
| `provider_name` | the **Meta** template name (`duukaflow_subscription_renewed`) |
| `language_code` | `en` / `en_GB` — must match the approved template |
| `template_category` | `UTILITY` / `MARKETING` / `AUTHENTICATION` |
| `template_status` | mirror of Meta's `APPROVED` / `PENDING` / `REJECTED` |
| `parameter_format` | `NAMED` / `POSITIONAL` |
| `is_mandatory` | transactional, not user-editable / not opt-out-able |

Rules:
- `duukaflow:whatsapp:sync-templates` pulls status from
  `GET /{waba-id}/message_templates` and flips `template_status`. Business owners may
  edit the **body wording**; they may not edit `provider_name` or `language_code`.
- A notification whose `template_status != APPROVED` is **not dispatched** — logged
  as `suppressed: template_not_approved`. Fail loudly at development time, quietly in prod.
- **Fix B6**: resolve by `template_key`, and make `template_key` equal to the
  notification `type`. Stop overloading `category`.
- Rendering: replace the naive `str_replace` in `WhatsAppTemplateService` with ordered
  `{token}` substitution driven by the `variables` map, so parameter order matches
  Meta's `{{1}}…{{n}}` deterministically.

### 5.4 Recipients

`whats_app_configs` is a **sender** config, not a recipient model. It has one
`business_phone`; it cannot express "notify the branch manager in branch 2" or
"this user doesn't want inventory alerts".

New `notification_recipients`:

| Column | Notes |
|---|---|
| `business_id`, `branch_id` (nullable) | branch-null = business level |
| `user_id` (nullable) | ties to a login when there is one |
| `label` | `owner` / `admin` / `manager` / `branch_manager` / `custom` |
| `channel` | `whatsapp` \| `email` |
| `address` | E.164 or email — **normalised on write** |
| `categories` json | opt-in/opt-out per category; `mandatory` categories always on |
| `is_active`, `verified_at` | unverified → treated as absent, logged |

- Seed at business creation: the owner, on **both** channels, `categories` =
  mandatory set only, `verified_at = null` (verify by first successful send).
- Resolution order for a notification: explicit recipient → matching `label` for the
  scope (branch first, then business) → business owner user → `business.phone`/`email`
  → **give up and log** `no_recipient`. Never guess a number.
- **Phone normalisation** to E.164. Uganda default `+256` when a local
  `07XXXXXXXX` is given. Reject anything that doesn't normalise; log and skip.
- **Mandatory categories** (not suppressible): `subscription`, `payment`, `security`.
  Everything else (`inventory`, `order`, `report`, `marketing`) is preference-checked.
  Billing notices cannot be opted out of — that is the whole point of them.
- Preferences live in `notification_recipients.categories` rather than a separate
  `notification_preferences` table — same data, one fewer join, and it makes
  "opted out everywhere" a single query.

### 5.5 Inventory alerts: state-based, with re-arm

`whatsapp.md` §7 is explicit and correct: `10 → 4` with threshold 5 fires **once**; a
re-check at 4 must not fire again. Approach:

- Fire from the **stock movement write path** as a domain event, not from a poller.
  `CheckNotificationsJob` becomes a re-arm sweeper only.
- Track alert state on the product (per branch), not in the message log:

  | `alert_state` | Meaning |
  |---|---|
  | `ok` | qty > reorder_level |
  | `low_stock_fired` | crossed below; do not re-fire |
  | `out_of_stock_fired` | reached 0 |
  | `suppressed` | business has no active recipient for `inventory` |

- **Re-arm** on replenishment: qty > reorder_level → `ok`, `episode++`. Next crossing
  fires again with a new dedupe key. This is the "reset/rearm" the brief asks for.
- Cooldown `WHATSAPP_STOCK_ALERT_COOLDOWN_HOURS=24` as a second guard.
- **Out-of-stock subsumes low-stock**: `0` also satisfies "below threshold", so
  fire `out_of_stock` only and suppress the redundant `low_stock`. One message per
  episode, not two.
- Report **top N** low-stock items in one message, not one message per product. Ten
  products crossing at once must not become ten WhatsApp messages.

### 5.6 Email channel (Amazon SES)

- **Blocker first:** `composer require aws/aws-sdk-php`. It is not installed.
- `MAIL_MAILER=ses-v2` (`SesV2Client`; `ses` is the legacy `SesClient`).
  Set in `.env.prod` already. `config/services.php` `ses` needs `token` for
  temporary creds — I'll add that key.
- **Mailables, not Laravel Notifications.** `App\Models\Notification` shadows
  `Illuminate\Notifications\Notification` (**B14**), and the `notifications` table is
  occupied. Mailables via a queued job avoid the collision entirely and give one code
  path with WhatsApp for logging/retry.
- Use `barryvdh/laravel-dompdf` (already a dependency, already used for the receipt
  and quotation PDFs) for the monthly report and receipt attachments. Reuse, don't
  add a PDF lib.
- **Mailables:** `BusinessWelcomeMail` · `SubscriptionMail` (parameterised:
  activated / renewed / plan-changed / payment-failed / expired / trial-ended — one class,
  one blade, six states) · `MonthlyPerformanceReportMail` (PDF) · `QuotationMail`
  (PDF) · `SaleReceiptMail` (PDF).
- **Unsubscribe:** `notification_subscriptions` (email, hashed token, categories,
  unsubscribed_at). Emit `List-Unsubscribe` and `List-Unsubscribe-Post: List-Unsubscribe=One-Click`.
  Transactional mail omits both. `EMAIL_PREFERENCE_CHECKED=true` gates it.
- **SES specifics:** the `From` must be a verified identity; the account is in
  **sandbox** until it requests production access (sandbox only sends to verified
  addresses — `MAIL_SES_SANDBOX` documents this). Prefer an IAM role over static keys
  in real prod. Set up **bounce/complaint/complaint** event publishing to an SNS/queue
  target → `notification_deliveries.status = failed`, which suppresses the address
  automatically so we stop hitting a hard bounce. Phase 6.
- **Do not** log message bodies at info level — receipts and reports contain customer
  data.

### 5.7 Scheduling

`routes/console.php` is the only live schedule. Delete dead `app/Console/Kernel.php`
(**B11**) and drop `Schedule::command("queue:work")` (**B12**) — move the worker to
supervisor / the compose service.

| Task | Schedule | Notes |
|---|---|---|
| `ProcessSubscriptionLifecycleWhatsAppJob` | `dailyAt('07:00')` | Was every 6h. Reminders are 2-day buckets, so 6h buys nothing. Timezone resolved **per business inside the job** (see below). |
| `GenerateMonthlyBusinessReportJob` | `monthlyOn(1, '08:00')` | Previous **completed** month. Was dead (**B3**/**B11**). |
| `CheckInventoryAlertsJob` | `hourly()` | Re-arm sweeper only. |
| `CheckNotificationsJob` | `everySixHours()` | Keep — in-app notifications. |
| `SyncWhatsAppTemplatesJob` | `daily()` | Pull Meta template status. |
| `ProcessSesSuppressionsJob` | `everyFifteenMinutes()` | Apply bounces/complaints. |

**Per-business timezone.** Laravel's `Schedule` takes one timezone; businesses are
across regions. So the schedule fires once and the job decides, per business, whether
"07:00 local" has arrived. Requires a `businesses.timezone` column (IANA name,
default `Africa/Kampala`). Without it, "01:00" means 01:00 UTC and a Kampala
business gets its monthly report at 04:00.

Jobs are `->withoutOverlapping()` and must be **safe to run repeatedly** — which the
dedupe reservation in §5.2 now guarantees.

### 5.8 Multi-tenancy in queue context — **B13**

`BaseModel`'s global scopes are `Auth::check()`-gated, so **every queued and
scheduled job currently reads across all tenants.** For a multi-tenant SaaS that is the
most dangerous bug in this feature.

Fix: add a `BusinessContext` singleton. `AppServiceProvider` binds it; the global
scope falls back to it when `Auth::check()` is false. Each job calls
`BusinessContext::run($businessId, fn () => ...)` at the top. Every query then scopes
itself even with no authenticated user, and nested jobs inherit it.

### 9. Security fixes bundled in

- `WhatsAppConfig` — add `protected $hidden = ['access_token', 'webhook_verify_token']`,
  and an `encrypted` cast on `access_token` (requires a data migration).
- `WhatsAppConfigController::index()` — return an explicit DTO, never the model.
- `WhatsAppConfigController::update()` — **add the ownership check** (**B8**). Any
  authenticated tenant can currently PATCH another business's config.
- `testMessage()` — **no input validation at all** today. Add a FormRequest, and
  require a verified recipient.
- Webhook route: **outside** `auth:sanctum`, but signature/secret verified, rate
  limited, and bodies logged without PII.
- Never log `access_token`, `webhook_verify_token`, or rendered message bodies.

---

## 6. The notification catalogue

The actual deliverable. "Most relevant and meaningful" was applied as a filter — the
excluded list at the end is as important as this table.

Legend — **E** email · **W** WhatsApp · **M** mandatory (not opt-out-able) ·
scope: **B** business / **Br** branch

| # | Notification | Ch | Sc | Meta template | Trigger | Dedupe identity |
|---|---|---|---|---|---|---|
| 1 | Business registered / welcome | E + W | B·M | `duukaflow_welcome` | after `BusinessService::create` **commits** | `registration:welcome:business-{id}` |
| 2 | Subscription activated | E + W | B·M | `subscription_activated` | `SubscriptionPayment` verified | `subscription:created:sub-{id}:payment-{id}` |
| 3 | Plan changed | E + W | B·M | `plan_changed` | `Subscription.plan_id` changes | `subscription:plan_changed:sub-{id}:plan-{new}:{effective}` |
| 4 | Subscription renewed | E + W | B·M | `subscription_renewed` | renewal payment verified | `subscription:renewed:sub-{id}:{period_start}` |
| 5 | Payment failed | E + W | B·M | `payment_failed` | verification rejected / gateway declines | `payment:failed:payment-{id}` |
| 6 | Subscription expired | E + W | B·M | `subscription_expired` | scheduled, `ends_at < now` | `subscription:expired:sub-{id}:{ends_at}` |
| 7 | Expiry reminder (every 2 days) | W | B·M | `subscription_expiring` | scheduled | `subscription:reminder:sub-{id}:bucket-{floor(d/2)}` |
| 8 | Free trial ended | E + W | B | `trial_ended` | `trial_ends_at < now` **and** no active sub | `trial:ended:sub-{id}:{trial_ends_at}` |
| 9 | Monthly performance report | **E (PDF)** + W one-liner | B | `monthly_report_ready` | 1st of month, prev. completed month | `report:monthly:business-{id}:{YYYY-MM}` |
| 10 | Quotation sent | E (PDF) | B→customer | — | `QuotationController::send` | `quotation:sent:quotation-{id}:v{version}` |
| 11 | Sale receipt | E (PDF) | B→customer | — | `SaleService` commit, when customer has email | `sale:receipt:sale-{id}` |
| 12 | New purchase order | W | Br | `purchase_order_created` | `PurchaseOrderService` commit | `order:purchase:branch-{id}:po-{id}` |
| 13 | New sale order | W | Br | `sale_order_created` | `SaleOrderService` commit | `order:sale:branch-{id}:so-{id}` |
| 14 | Low stock | W | Br | `low_stock_alert` | **transition** below `reorder_level`, top-N batched | `inventory:low_stock:product-{id}:episode-{n}` |
| 15 | Out of stock | W | Br | `out_of_stock_alert` | **transition** to 0 (suppresses #14) | `inventory:out_of_stock:product-{id}:episode-{n}` |

**Deliberately excluded** — these would be volume without meaning, and they are what
destroys a Meta quality score:

- ❌ **New user joined the business** — the owner doesn't need a message about their own
  hire. In-app notification is enough.
- ❌ **Every sale** — a busy shop generates hundreds/day. Receipts go to the *customer*,
  not the owner.
- ❌ **Every stock movement** — ditto. Only threshold transitions.
- ❌ **Payment received** — the owner is looking at the screen that took the payment.
  Only *failures* are worth interrupting them for.
- ❌ **Password resets / login alerts** — email only, and Laravel already ships this.
- ❌ **Marketing / promotional blasts** — separate opt-in list, separate consent, and
  `MARKETING` category billing. Explicitly out of MVP.
- ⚠️ **`daily_sales_summary`** — already seeded as a template
  (`WhatsAppService.php:41-86`) and contradicts this intent. **Recommend deleting it.**
- ⚠️ **`payment_reminder`** — also already seeded. Overdue-payment chasing *is*
  arguably relevant, but it belongs to **#3 (dunning)** and should ship with it,
  reusing the overdue logic already in `CheckNotificationsJob.php:121`.
  **Recommend deferring it there.**

**What makes this "relevant":** every row is a *state change the recipient did not
initiate and would otherwise not notice*, and each is deduplicated to once per real
event.

### 6.1 Multi-branch handling for #9 (monthly report)

`whatsapp.md` §5 explicitly asks. Decision:

- **Consolidated business-level by default.** One report, whole business, all branches.
  Owners think in business totals, not branch slices.
- **Per-branch breakdown inside the PDF** — reuse the existing
  `app/Services/Reports/BranchPerformanceReports.php:20` so we don't duplicate
  calculations. `whatsapp.md`: *"Do not duplicate business calculations unnecessarily
  inside the WhatsApp module."*
- Recipient: business-level recipients only. A branch manager does not get the
  business-wide report. If a branch-level report is wanted later, it is a separate
  notification type with its own dedupe key.
- Configurable via `config/notifications.php`, not hard-coded.

---

## 7. Database changes

| Migration | Contents |
|---|---|
| `create_notification_recipients_table` | §5.4. FK `business_id`, FK `branch_id` nullable, `channel`, `address`, `label`, `categories` json, `is_active`, `verified_at`. Unique `(business_id, branch_id, channel, address)`. |
| `create_notification_deliveries_table` | §5.1. **`dedupe_key` unique.** |
| `create_notification_subscriptions_table` | email unsubscribe: `email`, `token` (hashed, unique), `categories` json, `unsubscribed_at`. |
| `add_meta_fields_to_whatsapp_templates` | `provider_name`, `language_code`, `template_category`, `template_status`, `parameter_format`, `is_mandatory`. Index `(business_id, name, locale)`. |
| `add_alert_state_to_products` | `alert_state` string default `ok`, `alert_episode` int default 0. Per-branch → needs a pivot or a `product_branch_states` table depending on how branch stock is modelled. **Verify the branch-stock schema first.** |
| `add_timezone_to_businesses` | `timezone` IANA, default `Africa/Kampala`. |
| `fix_whatsapp_configs_unique_business` | unique index on `business_id` (**B7**), after a de-dupe pre-check. |
| `encrypt_whats_app_config_access_tokens` | re-encrypt plaintext tokens (**B8**). |
| `migrate_whatsapp_message_logs_to_deliveries` | backfill, then mark legacy table read-only. |

Existing `whats_app_*` table names stay — renaming is churn with no benefit.

---

## 8. Jobs, events, commands

### Events (fix **B2** — register `WhatsAppEventServiceProvider` **and** import the namespace)
`BusinessRegistered` · `SubscriptionCreated` · `SubscriptionPlanChanged` ·
`SubscriptionRenewed` · `PaymentFailed` · `SubscriptionExpired` · `FreeTrialExpired` ·
`LowStockAlert` · `OutOfStockAlert` · `PurchaseOrderCreated` · `SaleOrderCreated` ·
`QuotationSent` · `SaleCompleted`

> `SubscriptionRenewed` and `QuotationSent`/`SaleCompleted` **do not exist yet** and must be added.

### Listeners
One per event, all `ShouldQueue`, all `NotificationDispatcher::dispatch(...)`.
Nothing else. No listener touches a provider or a template string.

### Jobs
| Job | Responsibility |
|---|---|
| `SendWhatsAppNotificationJob` | replace `ProcessWhatsAppNotificationJob`; flip `sending`, resolve provider, send, write result, respect `attempt_count` + backoff |
| `SendEmailNotificationJob` | flip `sending`, hand the Mailable to SES, write result |
| `ProcessSubscriptionLifecycleWhatsAppJob` | fix **B5** selection; expiry + 2-day reminder + trial-ended, timezone-gated |
| `GenerateMonthlyBusinessReportJob` | fix **B3**; previous completed month; PDF + one-line WhatsApp nudge; consume existing report services |
| `CheckInventoryAlertsJob` | re-arm sweeper only |
| `SyncWhatsAppTemplatesJob` | pull Meta template status |
| `ProcessSesSuppressionsJob` | apply bounces/complaints |

Delete: `SendWhatsAppNotificationJob` (never dispatched, **dead**) ·
`SendSubscriptionExpiryNotificationsJob` (**B4**) · `SendFreePeriodExpiryJob` (**B4**) ·
`app/Console/Kernel.php` (**B11**)

### Commands
`duukaflow:whatsapp:sync-templates` · `duukaflow:whatsapp:test` ·
`duukaflow:whatsapp:limits` (messaging tier + quality score) ·
`duukaflow:notifications:test` (render every template for a business, no send) ·
`duukaflow:notifications:retry-failed`

### Webhooks
| Route | Auth | Handles |
|---|---|---|
| `GET /api/webhooks/whatsapp` | verify token | Meta handshake (`hub.mode`/`hub.verify_token`/`hub.challenge`) |
| `POST /api/webhooks/whatsapp` | signature | `messages` → `sent`/`delivered`/`read`/`failed`; `message_template_status_update` → template status. Must return 200 fast, always. |
| `POST /api/webhooks/ses` | signature | bounce / complaint / delivery |

---

## 9. Implementation sequence

> **Progress:** Stage 0 ✅ · Stage 1 ✅ · Stage 2 chunks 1–2 ✅ · remainder ⬜
> See [`undone.md`](undone.md) for the current starting point, and `to-check.md` for
> deviations from the stages below that were deliberate.

**Stage 0 — Unbreak** *(must be first; nothing else is testable until it lands)*
Fix B1, B2, B3, B4, B11, B12, B6, B7, B8. Delete the dead jobs and `Console/Kernel`.
*This alone restores business registration, the event pipeline, and the monthly
report schedule.*

**Stage 1 — Foundation** Schema (§7) · `BusinessContext` (**B13**) · `config/notifications.php` ·
`composer require aws/aws-sdk-php` · `NotificationDispatcher` · `NotificationRecipientResolver` ·
`NotificationPreferenceResolver` · template renderer rewrite · `NotificationChannel` interface.

**Stage 2 — Email (SES)** Mailables · `SesMailChannel` · `SendEmailNotificationJob` ·
unsubscribe · SES config completion.

**Stage 3 — WhatsApp (real Meta)** Real `MetaWhatsAppProvider` (Cloud API v26, template
messages, E.164 normalisation) · webhook controller + status reconciliation ·
`duukaflow:whatsapp:*` commands · throttle rails.

**Stage 4 — Catalogue wiring** All 15 notifications · product alert-state machine ·
subscription lifecycle · monthly report job · quotation send · sale receipt.

**Stage 5 — Tests** §10.

**Stage 6 — UI** Notification log · recipient management · per-category preferences ·
template editor (body wording only) · SES bounce dashboard.

**Stage 7 — Flip** `WHATSAPP_PROVIDER=meta` **last**, after test coverage — per
`industry-features.md` §2. Until then `demo` stays the default and prod is unaffected.

**Effort:** ~10–12 dev days. Stage 0 is ~1 day and unblocks the most.

---

## 10. Testing plan

| Area | Test |
|---|---|
| Dedupe | Same key dispatched twice → **one** `notification_deliveries` row, one send. Concurrent dispatch (parallel) → still one. |
| Retry | Job fails then succeeds → 1 row, `attempt_count=2`, one provider call per attempt. |
| Ambiguous timeout | Row stuck `sending` → **no** auto-resend; webhook then flips it. |
| Provider | `MetaWhatsAppProvider` hits the right URL/body/headers (HTTP fake); 4xx → `failed` with code; 429 → backoff. |
| Templates | Each of the 15 renders with a full variable set; unapproved template → suppressed. |
| Recipients | Branch recipient preferred over business; no recipient → logged, not thrown; bad phone → skipped. |
| Preferences | Inventory opt-out suppresses #14/#15 only; subscription opt-out **cannot** suppress #2–#7. |
| Inventory | `10→4` (thr 5) fires once; re-check at 4 silent; `4→12` rearms; `→0` fires out-of-stock and **not** low-stock. |
| Subscription | Expiry once per `ends_at`; reminder buckets 0,2,4,6,8 and no bucket twice; resumes silently on resubscribe. |
| Monthly report | Previous completed month; per-business timezone; deduped per `YYYY-MM`. |
| Tenant scope | Job for business A never reads/writes business B (**B13** regression test). |
| Email | SES mailable builds, attaches PDF, carries unsubscribe headers on preference-checked mail and not on transactional. |
| Webhook | Handshake, status transitions, unknown message id, malformed body → 200, no throw. |
| Security | `GET /whatsapp` never returns `access_token`; `PUT /whatsapp/{id}` rejects another tenant's id. |

Tenant-scoping test is mandatory on **every** one of these — `industry-features.md` §Notes.

---

## 11. Open decisions

1. **Template-only, or allow free text in the 24h window?**
   Recommend **template-only** (`WHATSAPP_ALLOW_FREE_TEXT=false`). Free text is only
   ever valid inside a window the customer opened, and we have no inbound message
   handling yet — so any free-text send would be a policy violation. Revisit when
   inbound exists.
2. **`businesses.timezone` column, or a settings table?**
   `CoreBusinessSettings` is a family of one-off per-feature tables, not a KV store.
   A column on `businesses` is simpler and cheaper to read on every scheduled job.
   Recommend **column**. Confirm.
3. **One `notification_deliveries` table, or extend `whats_app_message_logs`?**
   Recommend the new table + deprecate the old (§5.1). Costs one migration, buys one
   place to answer "did they get it?" for both channels.
4. **Delete `daily_sales_summary` and defer `payment_reminder`?**
   Both are already seeded and both conflict with "relevant and meaningful"
   (§6). Recommend **delete + defer to #3 dunning**.
5. **Monthly report nudge: `UTILITY` or `MARKETING` template category?**
   Recommend `UTILITY` (cheap tier, far higher limits). Confirm.
6. **Per-branch or consolidated monthly report?**
   Recommend consolidated business + per-branch table inside the PDF (§6.1).
7. **Is #11 (sale receipt by email) in scope now?**
   It's a customer-facing email and arguably #1's territory, not #2's. Recommend
   **defer** unless you want it as a Quotation companion.
8. **AWS auth: static keys or IAM role?**
   `.env.prod` is written for static keys with `AWS_USE_IAM_ROLE=false` as the
   documented switch. If prod runs on ECS/EC2 with an instance profile, drop the keys
   entirely. Which is it?

---

*Plan written 2026-09-25. Stages 0–1 and Stage 2 chunks 1–2 implemented — resume from
[`undone.md`](undone.md); deviations and bugs found are recorded in `to-check.md`.*
