# Undone — Customer Communication (SES email + WhatsApp)

**Start here in the morning.** This is the working checklist for
[`email_whatsappp_notifications.md`](email_whatsappp_notifications.md). The plan is the
spec; this file is what is actually left to build. Finished items are kept and marked
✅ so progress is visible at a glance rather than inferred from a git log.

Last updated: after the admin reports PDF download.

---

## Where we are

Stage 0, Stage 1, the first two chunks of Stage 2, **Stage 2 chunk 3 (PDF attachments)**,
and the **Stage 3 send path** are done and green. On top of chunk 3, the **monthly report
is now downloadable from `/admin reports`**. Stage 3 now needs the **status webhook**, which
finally has something to reconcile against: real Meta message ids are being stored.

| Suite | Result |
|---|---|
| `tests/Feature/WhatsApp/` | ✅ 217 tests, 680 assertions |
| `tests/Feature/Reports/` | ✅ 32 tests, 108 assertions (new) |
| Full suite | 394 tests, 1219 assertions, **3 failures** — all pre-existing and unrelated (see [Known failures](#known-failures)) |
| Plan §6 count | ✅ Reconciled: 14 live, 1 deferred |

---

## Start here tomorrow

### 🟡 Stage 3 — WhatsApp (real Meta)

`MetaWhatsAppProvider::sendMessage()` now performs a **real send**. Template *listing*
against `GET /{version}/{wabaId}/message_templates` has always been real and still syncs
daily.

- ✅ `POST /{version}/{phoneNumberId}/messages` — template message body, `messaging_product`,
  `recipient_type`, E.164 normalisation (done at the boundary, not the caller).
- ⬜ Status webhook + reconciliation. `notification_deliveries` states and the
  ambiguous-send design now have a producer to feed them: a real Meta message id is
  stored on every accepted send, which is what the webhook matches on.
- ⬜ Throttle rails. §3 is emphatic that volume is the enemy; the 429 path needs backoff
  that the retry config can actually express.

#### The send is decided by classification, not by the HTTP call

The substance of `sendMessage()` is deciding, for every way the call can end, whether Meta
took the message. The two ways of getting that wrong fail in opposite directions, and both
fail silently:

| Outcome | Classification | Why |
|---|---|---|
| 2xx with `messages[0].id` | accepted | Meta took it; store the id for the webhook |
| 2xx with **no readable id** | ambiguous | Accepted, but nothing to reconcile on |
| 4xx template/param error | rejected, Meta's code | A bug in our payload, not a transient fault |
| 401/403 | rejected, `auth` | Token problem; nothing was sent |
| **429** | rejected, `throttled` | Meta answered, so nothing was sent |
| 5xx | ambiguous | May have failed *after* accepting |
| Dead connection | ambiguous | May have reached Meta and been accepted |
| Unusable recipient / no token | rejected | Refused before any request |

Three of these were decided deliberately against the obvious reading:

**A 429 is not ambiguous.** Meta answered, so nothing was sent and no status webhook will
ever mention that message. Filing it as ambiguous leaves the row in `sending` awaiting a
callback that cannot exist — the notification is lost with no error displayed. It is also
not an ordinary rejection, since it clears and the message should go out later, so it gets
its own code for the throttle rails to attach backoff to. **Stage 3's next piece has to
make `throttled` retryable**; today it is recorded as `failed`, which is honest and
recoverable by hand but not automatic.

**A 5xx is ambiguous even though Meta answered it.** The tempting rule is "answered means
definitive", but a 5xx may have failed after accepting. The worst case here is a delivery
recorded as unresolved; the worst case on the other side is a customer messaged twice.

**A 2xx we cannot parse is ambiguous, not accepted.** A guard test caught this. Meta's send
endpoint always returns `messages[0].id`, so a success with no readable id means the
response is not a send response. Returning it as-is read downstream as a *successful send
with a null id* — the same fabricated-success shape the old stub returned, reached by a
different road, and equally invisible: the dashboard says delivered and the webhook can
never match a row with nothing to match on.

#### Two smaller consequences worth recording

`ChannelResult::ambiguous()` hardcoded `errorCode = 'ambiguous'`, which collapsed a 5xx, a
dead connection and an unparseable reply into one value at the moment of recording — three
different operational problems, one undiagnosable bucket. It now takes the provider's code,
defaulting to `ambiguous` so nothing else changes.

`validateConfiguration()` passes if *any* of business phone, token or phone number ID is
set, so a config with only a business phone is "valid" and then cannot address a send at
all. `sendMessage()` therefore re-checks token and phone number ID at the boundary and
refuses with `not_configured` before attempting anything. Not thrown: nothing was
transmitted, so it is a definitive rejection.

**18 tests** in `MetaWhatsAppSendTest.php` cover the body shape, the version and phone
number in the URL, boundary normalisation, and each row of the table above as observed
through the delivery row it produces. The two 2026-era classifications were verified as
guards by re-breaking them and confirming 3 tests fail.

#### ✅ Cloud API version — settled, and not the one the plan assumed

**v25.0**, via `config('services.whatsapp.graph_api_version')`, overridable with
`WHATSAPP_GRAPH_API_VERSION`. Both settled questions here, and the plan's answer was not
the right one.

Meta expires Graph API versions on a fixed schedule and then stops serving them. Checked
against Meta's own changelog on 2026-09-26:

| Version | Released | Expires | |
|---|---|---|---|
| v26.0 | 2026-07-29 | TBD | latest, but no committed expiry and 2 months old |
| **v25.0** | **2026-02-18** | **2028-07-29** | **chosen** — what Meta's current docs examples use |
| v24.0 | 2025-10-08 | 2028-02-18 | |
| v23.0 | 2025-05-29 | 2027-10-08 | |
| v22.0 | 2025-01-21 | 2027-05-20 | |
| v21.0 | 2024-10-02 | **2027-01-21** | ← what this code hardcoded; **under 4 months away** |
| v20.0 | 2024-05-21 | 2026-09-24 | already expired |

The plan said v26.0. v25.0 is preferred over it because v26.0's expiry is still `TBD` and
it was two months old at the time of writing, while v25.0 is the version Meta's own
documentation demonstrates and carries a confirmed ~2-year runway.

**The real finding is that v21.0 was a trap.** The obvious "keep v21.0, it already works
and it avoids mixing versions" answer would have bought a forced migration in under four
months. The doc's instinct not to just keep the working version was right, for a
sharper reason than it gave.

Two details worth carrying:

- The version is **config, not a URL fragment**. It was previously inlined at
  `MetaWhatsAppProvider.php:68`, so "settle on one version" meant editing code and the send
  path added in Stage 3 could easily have landed on a different version from the listing
  path. Every Graph URL now resolves through one `graphUrl()` helper, so listing and
  sending cannot drift apart.
- Meta only honours calls to an older version if the app **has actually called** it. That
  restriction applies to moving *back*. Jumping forward to v25.0 is always permitted, so
  this change is safe regardless of the app's call history.

#### ✅ The stub fails closed now

`sendMessage()` used to return `'status' => 'sent'` with a `provider_message_id` that was
an md5 of the recipient and body. Nothing had been transmitted. That is the worst possible
shape for an unimplemented send: the delivery is marked sent, the dashboard says it went
out, nothing retries it, and the customer's only symptom is a message that never arrives.

It needed no bug to trigger — setting `WHATSAPP_PROVIDER=meta` was sufficient, and every
notification would have "succeeded" silently.

This was first made to throw a `LogicException`, then made to return a structured
rejection once the send was implemented. **The invariant that matters was never "it
throws" — it is "it never claims a send it did not make."** Both forms satisfy it; the
rejection is better, because a throw was recorded as an *ambiguous* send and left the row
in `sending` awaiting a webhook, which is wrong for a provider that sent nothing at all.
The guard test now pins the invariant rather than the old mechanism, asserting that no
response shape carries a `status` key or a `provider_message_id` it did not earn.
5 tests in `tests/Feature/WhatsApp/MetaWhatsAppProviderGuardTest.php`.

Stage 7 already said flip the provider last, and that instruction still has teeth.

#### ✅ The send path's own outcomes had never been exercised

Closing the stub exposed a gap next to it. The sent path had only ever been driven
through the demo provider, and **nothing asserted the provider message id** — so two
branches had never run:

**The message id was silently dropped.** `WhatsAppChannel::interpret()` read only
`message_id` and `messages[0].id`. The demo provider returns `provider_message_id`, so
every demo send was marked `sent` with a **null** `provider_message_id`. Verified, not
inferred: a probe printed `status=sent provider=demo provider_message_id=NULL`.

This is the id the status webhook matches on, so the webhook — the next piece of Stage 3
— would have had nothing to correlate against, and every delivery would have sat in
`sending` forever. `interpret()` now reads all three shapes. The channel's own docblock
records why the disagreement was invisible: the providers return different keys, and only
one of them was read.

**The exception branch had no coverage at all.** `SendNotificationJob` catches `Throwable`
from the transport, leaves the row in `sending` with `error_code = 'exception'` and
rethrows, and a retry then stands down because `isAmbiguous()` is true. All correct — and
none of it tested. That is now the *primary* path for anyone who sets
`WHATSAPP_PROVIDER=meta`, since the provider throws by design.

7 tests in `tests/Feature/WhatsApp/SendPathOutcomeTest.php`: a successful send stores a
non-null id, a throwing provider never leaves a row claiming the customer was reached, a
thrown send is not retried into a duplicate, and Meta's nested `messages[0].id` shape is
pinned *before* Stage 3 exists to produce it. The provider is faked rather than the
channel, because the bug lived in the seam between a provider's response and the delivery
row — exactly what a hand-written unit test skips. Reverting the one-line fix fails 2 of
them, so they are guards rather than decoration.

### ✅ Stage 2 · chunk 3 — PDF attachments (done)

`report.monthly` and `quotation.sent` now attach a real PDF. Generator was **not** an
open question — `barryvdh/laravel-dompdf` was already installed and already used by
`QuotationController::pdf()` and `ReceiptController::pdf()`; the earlier note claiming
otherwise was wrong. The quotation attachment reuses `pdfs.quotation` rather than a
second template, so the emailed and downloaded documents cannot drift.

Built: `AttachmentBuilder` contract, `AttachmentRegistry` (mirrors `ChannelRegistry`),
`QuotationPdf`, `MonthlyReportPdf`, `MonthlyReport` value object,
`pdfs.monthly-report`, an `attachments` key on the two catalogue entries, and
`NotificationMail::attachmentFiles()`. 15 tests in
`tests/Feature/WhatsApp/NotificationAttachmentTest.php`, asserting on the built MIME
message rather than a `Mail::fake()` record.

Three things about it worth knowing before changing it:

- **A PDF failure never fails the email.** `AttachmentRegistry::buildFor()` catches and
  logs; the mail goes out with its body. A Blade typo in a view must not cost the
  customer the notification.
- **The report is rendered purely from the delivery's stored payload**, never re-queried,
  so the PDF cannot describe a different month from the body beside it. The payload key
  contract is documented on `MonthlyReport` and pinned by a test — the Stage 4 trigger
  has to match it exactly.
- **The method is `attachmentFiles()`, not `attachments()`.** `Illuminate\Mail\Mailable`
  declares `attachments()` privately for its own envelope de-duplication; shadowing it
  makes Laravel's internal call land on ours. This cost 14 tests and one confusing
  "undefined method" before it was found.

**Not verified end to end**, and this is the loose end: nothing dispatches
`report.monthly` or `quotation.sent` yet, so no PDF has been through a real send. That
is Stage 4 work, not a defect in this chunk.

### ✅ Monthly report download in admin reports (done)

The monthly document is now reachable from `/admin/reports`, which is where someone
actually goes to read it. Built: `MonthlyPerformanceReport` service (the figures),
`MonthlyPerformanceReport` controller, two routes, the `MonthlyPerformanceReport` card
with a month picker, and a `monthlyPerformancePdf` mutation in the reports query slice.
15 tests.

Decisions, so they are not relitigated by accident:

- **The figures live in a service, not in the controller.** `MonthlyPerformanceReport`
  service returns exactly the payload `MonthlyReport` parses, so the emailed PDF, the
  downloaded PDF and the email body are all derived from one implementation. Stage 4's
  monthly trigger should call that service rather than re-deriving the numbers — that is
  the whole reason it exists as a service.
- **Sourced from `cash_flows`, not from `sales`/`purchases`/`expenses`.** The Branch
  Performance card directly above it on the same page already reads `cash_flows`, and
  `cash_flows` carries `business_branch_id`, which is what makes per-branch scoping
  possible. Deriving it from the raw documents would print a total that contradicts the
  card next to it.
- **One document per branch; `branch_id` is required, not a filter.** See resolved #6
  below. The service takes a `BusinessBranch` and adds
  `where cash_flows.business_branch_id = $branch->id` explicitly rather than leaning on the
  branch global scope, because a business admin's scope admits every branch and would let a
  document headed with one branch's name carry another's figures. The filename carries the
  branch (`monthly-report-kampala-road-august-2026.pdf`) so two branches of one business
  don't land in a downloads folder as two identically named files.
- **Branch authorisation is resolved once, in one place.**
  `EffectiveBranchScope::resolveReportBranch()` reuses `branchesFor()` so the permitted set
  cannot drift from the rows the scope actually admits. Both report controllers call it;
  `BranchPerformanceReports` treats an absent id as "all branches" (that card compares them)
  while the monthly report requires one (there is no company-wide document to serve).
- **Month-scoped, not the shared period filter.** The document is titled with a calendar
  month and the email's dedupe identity is
  `business:{id}:{branch_id}:{YYYY-MM}`, so a "last 30 days" version of it would be a
  different document wearing the same name. Defaults to the last *completed* month — the
  current one is still accumulating and would understate itself.
- **A month with no transactions still downloads.** An empty month is a report full of
  zeroes, not a failure, and is plausibly exactly what someone wants to forward on.
- **The quotation was left alone.** It already downloads from `/admin/quotations`.
  Putting a second copy of the same document in reports would be two entry points for one
  file, not a feature.
- **`businesses.timezone` is a real column**, which settles the open question in
  `to-check.md` #2 in favour of the column. The month boundary is drawn in the
  business's own timezone.

---

## Remaining, in order

### ⬜ Stage 4 — Catalogue wiring

The 14 notifications are defined, provisioned and testable. None are wired to the
events that should fire them.

- All 14 catalogue notifications dispatched from their real triggers.
- Product alert state machine (§5.5) — `10→4` fires once, re-check at 4 stays silent,
  `4→12` rearms, `→0` fires out-of-stock and **not** low-stock. This is the piece with
  the most room to be subtly wrong and the most visible when it is.
- Subscription lifecycle job — expiry once per `ends_at`, reminder buckets 0,2,4,6,8,
  no bucket twice, silent resume on resubscribe.
- Monthly report job — previous completed month, per-business timezone, deduped per
  `YYYY-MM`. **See the hazard below before writing it.**
- Quotation send.

#### ✅ Investigated: the three scheduled jobs are not the same problem

This section previously claimed all three jobs "bypass the whole pipeline" and that
retiring them was needed to avoid sending customers two monthly reports. **That was wrong
about two of the three**, and checking it properly changed the plan.

| Job | What it actually does | Verdict |
|---|---|---|
| `CheckNotificationsJob` | Writes in-app `notifications` rows **only** | **Keep.** Not a duplicate-send risk |
| `GenerateMonthlyBusinessReportJob` | Cannot execute at all | **Dead code.** Delete |
| `ProcessSubscriptionLifecycleWhatsAppJob` | Really does send WhatsApp | **The real hazard.** Fix, then retire in Stage 4 |

**`CheckNotificationsJob` must stay.** `NotificationService` only ever calls
`Notification::create` — it sends no WhatsApp, no email, no SMS. And `NotificationController`
serves those rows from `/notifications`, including the unread count. So it feeds a live
user-facing feature that the catalogue pipeline does not write to at all. Retiring it
would delete the in-app notification feed to prevent a duplicate that cannot happen.

**`GenerateMonthlyBusinessReportJob` has never run.** `purchases` is scoped by
`business_branch_id` and has no `business_id` column, so `Purchase::where('business_id', …)`
raises an SQL error on the first business and the job fatals. Every run has failed. So the
hardcoded-recipient leak in it was **latent, not live**, and deleting it costs no coverage,
because there has never been any coverage. It also computed a company-wide consolidated
total — the exact document shape that was rejected in favour of one report per branch —
using `Carbon::now()` rather than the `businesses.timezone` column, with a dedupe key
carrying no `branch_id`.

**`ProcessSubscriptionLifecycleWhatsAppJob` is the only genuine double-send hazard.** Its
dedupe lives in `whatsapp_message_logs.dedupe_key` and its keys are built on a completely
different scheme from the catalogue's, so it **cannot** suppress `subscription.expired` or
`subscription.expiring` when those are wired. Two further defects found in it:

- `handleExpiryReminder()` passes `days_since_expiry` into the `days_remaining` field, so
  the message would say "N days remaining" where N is days *overdue*.
- Its reminder is guarded by `ends_at->isPast()`, so it can only ever fire **after**
  expiry. The catalogue's `subscription.expiring` is a *pre*-expiry reminder. The legacy
  "reminder" is not the same notification under an older name.

#### ✅ The hardcoded recipient was nine send paths, not one

`'+256731794401'` was a fallback recipient in the monthly report job, **seven** listeners
(`SubscriptionCreated`, `SubscriptionPlanChanged`, `SubscriptionExpired`,
`FreeTrialExpired`, `PaymentFailed`, `SaleOrderCreated`, `PurchaseOrderCreated`) and
`SubscriptionController` — and a default *sender identity* in `config/services.php` and
`ProcessWhatsAppNotificationJob`. Any business without a phone on file had its
subscription, payment, sale and purchase notifications delivered to one personal handset.

Removed at source in all nine, plus one guard in `WhatsAppNotificationService::
queueBusinessNotification()` — the single dispatch point all ten `queue*()` methods funnel
through, so the pattern cannot come back at the next entry point. It logs a warning and
returns `queued => false` rather than throwing, because a missing phone number must not
abort the subscription or sale that triggered it.

The new pipeline was never exposed to this: `RecipientResolver` returns null instead of
guessing, and the channel rejects a delivery with no address. This was a legacy-layer
habit, which is itself an argument for retiring that layer rather than patching it
indefinitely. 4 tests in `tests/Feature/WhatsApp/LegacyHardcodedRecipientTest.php`,
including a source-level guard so a new listener copying the old line is caught.

`StageZeroRepairTest` and `PhaseOneMessagingTest` cover `ProcessWhatsAppNotificationJob`,
which is the legacy *sender* rather than a scheduled job — so that deletion still is not
automatic, and it is out of scope here.


### ⬜ Stage 5 — Tests

Work through the §10 table. Tenant-scoping is **mandatory on every row**, not just the
one that names it.

### ⬜ Stage 6 — UI

Notification log · recipient management · per-category preferences · template editor
(body wording only) · SES bounce dashboard. Deliberately untouched so far — no
UI or design-system file has been touched by this work.

### ⬜ Stage 7 — Flip the provider

`WHATSAPP_PROVIDER=meta`, **last**, after test coverage. `demo` stays the default until
then so prod is unaffected.

---

## Needs you, not code

These block real-world behaviour and cannot be done from the repo.

1. **SNS topic + SES event publishing.** Create an SNS topic with an **HTTPS**
   subscription to `POST /api/webhooks/ses`, and enable SES event publishing for
   bounces, complaints and deliveries. The endpoint is written and tested but has never
   received a real event — without this it is never called at all.
2. **AWS auth.** Plan §11.8. `.env.prod` is written for static keys with
   `AWS_USE_IAM_ROLE=false` as the switch. If prod runs on ECS/EC2 with an instance
   profile, drop the keys entirely. Unanswered.
3. **Live SES send.** Never exercised. `.env` is still `MAIL_MAILER=log`; `.env.prod`
   uses `ses-v2`. Needs credentials, a verified sender identity, and a decision on
   sandbox access.

## Open decisions carried from plan §11

- **#1 Template-only vs free text.** Recommend template-only
  (`WHATSAPP_ALLOW_FREE_TEXT=false`); we have no inbound handling, so free text is a
  policy violation. Revisit when inbound exists.
- ~~**#2 `businesses.timezone` column vs settings table.**~~ ✅ **Resolved.** It is a real
  column on `businesses`, so the column wins over a settings row and the month boundary is
  drawn in the business's own day.
- ~~**#6 Per-branch or consolidated monthly report.**~~ ✅ **Resolved: per branch, one
  document per branch.** Recommended consolidated with a per-branch table; rejected after
  the user asked for it explicitly. A blended company total reads as if it were somebody's
  branch, and the person who has to act on a monthly figure is the branch manager. So
  `branch_id` is required, a branch-restricted user is always scoped to their own branch, a
  business admin with several branches must choose one, and a business with exactly one
  branch needs no choice. There is no company-wide document at all — the API answers 422
  rather than inventing one, because a default to the first branch would hand back a real,
  plausible report about a branch nobody asked for. Cross-branch comparison stays on the
  Branch Performance card, which is a different card for a different reader. **Consequence
  for Stage 4:** the notification dedupe key must become
  `business:{id}:{branch_id}:{YYYY-MM}`, not `business:{id}:{YYYY-MM}`, or one branch's
  report suppresses another's.
- ~~**#4/#7 Catalogue scope.**~~ ✅ **Resolved.** `daily_sales_summary` and
  `payment_reminder` deferred, sale-receipt email deferred. The plan said the first two
  were "already seeded as templates" and needed deleting — that was wrong on both counts.
  `daily_sales_summary` was never a template at all and appears nowhere in the codebase;
  `payment_reminder` genuinely was, and replacing the old hand-written seeding with
  `TemplateProvisioner` already removed it. Plan §6 now says **14 live, 1 deferred**
  (#11 sale receipt, row kept for stable numbering) and the "All 15" references in §9 and
  §10 are corrected. Nothing left to do.
- **Mandatory categories do not match plan §5.4.** The brief says `subscription`,
  `payment`, `security`. The catalogue has **no `security` category** and marks `order`
  mandatory instead. The code now derives mandatory from the catalogue, so it is
  internally consistent, but it is not what the brief asked for. Needs a ruling.
- **New businesses get no inventory or report mail.** `RecipientProvisioner` seeds only
  mandatory categories, so optional ones arrive empty and nothing sends. Probably a
  deliberate default, but nobody has confirmed it, and "reports are silently never
  emailed to a new customer" is a bad failure mode to leave unasked.

---

## Known failures — not ours, do not chase

All three pre-date this work and are unrelated to notifications:

- `Tests\Feature\NotificationTest::test_unread_count_returns_grouped_by_type`
- `Tests\Feature\POS\PosCheckoutTest::test_checkout_requires_auth`
- `Tests\Feature\POS\PosSearchTest::test_search_request_requires_auth`

## Commands

```bash
# Tests MUST target the test database. phpunit.xml points at dev `inventory`, and
# RefreshDatabase will drop it.
cd api
DB_HOST=127.0.0.1 DB_DATABASE=inventory_test ./vendor/bin/phpunit tests/Feature/WhatsApp/
DB_HOST=127.0.0.1 DB_DATABASE=inventory_test ./vendor/bin/phpunit
```

- `php artisan schedule:list` — verify `ProcessSesSuppressionsJob` is on the 15-minute
  cadence and `duukaflow:whatsapp:sync-templates` is at 06:30.
- `php artisan duukaflow:notifications:backfill-recipients` — existing businesses.
- `php artisan duukaflow:whatsapp:sync-templates` — pulls live Meta approval status.
- The `mysqli.so` startup warning on every PHP command is a broken local extension and
  is harmless here. There is **no `pdo_sqlite`** on this host at all, so
  `DB_CONNECTION=sqlite` is not an option — the only drivers are `mysql` and `pgsql`.
- **Running inside the `duukaflow-backend-1` container needs
  `APP_ENV=testing MAIL_MAILER=array` in front of the command.** The container exports
  `APP_ENV=local` and `MAIL_MAILER=log` as real environment variables, which win over
  `phpunit.xml` and over `.env.testing`, so the suite boots against `.env` with the log
  mailer and every test touching sent mail dies on `LogTransport::flush()` — a method
  that does not exist on the log transport. `force="true"` on the `phpunit.xml` entries
  does *not* fix it; only the shell prefix does. The host invocation above needs no such
  prefix, which is why the documented command does not have one.

## Three traps worth remembering

- **Never run `pint` unscoped.** Pointed at `app/` and `routes/` it reformatted **322**
  unrelated files. Everything was reverted; only the intended files were kept. Always
  pass explicit paths, then check `git status` afterwards.
- **`notification_recipients` is scoped by the global branch scope.** `->first()` in a
  test is ambiguous and silently returns the wrong tenant's row. Query by
  `business_id` + `address`, or `->sole()` on a known filter.
- **Do not name a Mailable helper `attachments()`.** `Illuminate\Mail\Mailable` declares
  that method *privately* for its own envelope de-duplication. Redeclaring it looks
  legal, compiles, and passes reflection — then every email in the suite fails with
  `Call to undefined method`, because Laravel's internal call is landing on your method.
  `buildAttachments()` is taken by the parent too. The helper is `attachmentFiles()`.
- **PHP class names are case-insensitive, so `use ...\Facade\Pdf;` and `use ...\PDF;`
  collide.** `use Barryvdh\DomPDF\Facade\Pdf;` next to `use Barryvdh\DomPDF\PDF;` is
  `Cannot use ... as PDF because the name is already in use` — a fatal at class-compile
  time, which PHPUnit reports as `Premature end of PHP process`, pointing at no file and
  mentioning no import. Alias the concrete class (`PDF as DomPdf`).
- **Never `groupByRaw('1')` for a totals-only query.** Postgres resolves an ordinal
  `GROUP BY` against the select list, so it groups by the first `SUM()` and fails with
  `aggregate functions are not allowed in GROUP BY`. MySQL accepts it, so it passes in dev
  and dies in production. A bare aggregate query needs no `GROUP BY` at all.
- **Do not join a tenant-scoped query to another tenant table.** `business_branches` has a
  `business_id` too, and the global scope in `BaseModel` emits an *unqualified*
  `where business_id = ?`, so the join turns every query into
  `Ambiguous column: business_id is ambiguous`. Qualifying the scope's own output is not
  an option; it is framework-wide. Look the names up in a second query instead.
  **`BranchPerformanceReports` had exactly this join and was 500ing on every request** — the
  top card on the reports page, with no test at all. Check every other report service for
  the same shape before assuming this was a new mistake rather than an existing one.
- **Never put two Blade directives back to back.** Blade anchors directives on `\B@`, and
  `@` is a non-word character, so in `@endif@if` the second `@` sits directly after the
  word character `f` — that is a word boundary, `\B` fails, and the second directive is
  left in the output as literal text. The template still compiles, so the failure arrives
  as `syntax error, unexpected token "endif"` from the compiled view, a long way from the
  line that caused it. Put directives on their own lines.
- **`(array) $model` is not `$model->getAttributes()`.** Casting an Eloquent model to an
  array yields its private properties under NUL-prefixed mangled keys with the real
  columns buried inside `['attributes']`. A report built that way renders every figure as
  zero and nothing anywhere reports an error.

## Where the reasoning is written up

[`to-check.md`](to-check.md) — every deliberate deviation from the plan, the bugs found
during implementation, and the operational setup notes, stage by stage. Read it before
changing anything in here; several decisions look like bugs and are not.
