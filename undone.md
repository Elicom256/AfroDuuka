# Undone — Customer Communication (SES email + WhatsApp)

**Start here in the morning.** This is the working checklist for
[`email_whatsappp_notifications.md`](email_whatsappp_notifications.md). The plan is the
spec; this file is what is actually left to build. Finished items are kept and marked
✅ so progress is visible at a glance rather than inferred from a git log.

Last updated: after the admin reports PDF download.

---

## Where we are

Stage 0, Stage 1, the first two chunks of Stage 2, and **Stage 2 chunk 3 (PDF
attachments)** are done and green. On top of chunk 3, the **monthly report is now
downloadable from `/admin/reports`**. The next chunk is **Stage 3**, which is the first
genuinely new thing since chunk 2 — a real HTTP call to Meta instead of the filesystem
and log.

| Suite | Result |
|---|---|
| `tests/Feature/WhatsApp/` | ✅ 182 tests, 589 assertions |
| `tests/Feature/Reports/` | ✅ 15 tests, 60 assertions (new) |
| Full suite | 342 tests, 1080 assertions, **3 failures** — all pre-existing and unrelated (see [Known failures](#known-failures)) |
| Plan §6 count | ✅ Reconciled: 14 live, 1 deferred |

---

## Start here tomorrow

### ⬜ Stage 3 — WhatsApp (real Meta)

`MetaWhatsAppProvider::sendMessage()` is still a **stub**. Template *listing* against
`GET /v21.0/{wabaId}/message_templates` is real and syncing daily; sending is not.

- `POST /v21.0/{phoneNumberId}/messages` — template message body, `messaging_product`,
  `recipient_type`, E.164 normalisation (do this at the boundary, not the caller).
- Status webhook + reconciliation. The `notification_deliveries` states and the
  ambiguous-send design already exist for this: an ambiguous send is left in `sending`
  and the webhook settles it, rather than being retried and double-delivering.
- Throttle rails. §3 is emphatic that volume is the enemy; the 429 path needs backoff
  that the retry config can actually express.
- Confirm Cloud API version. Plan says v26.0; the listing path was written against
  v21.0. **Do not mix versions in the same provider** — settle on one.

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
  `cash_flows` carries `business_branch_id`, which the per-branch table needs. Deriving
  it from the raw documents would print a total that contradicts the card next to it.
- **Month-scoped, not the shared period filter.** The document is titled with a calendar
  month and the email's dedupe identity is `business:{id}:{YYYY-MM}`, so a "last 30 days"
  version of it would be a different document wearing the same name. Defaults to the last
  *completed* month — the current one is still accumulating and would understate itself.
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

#### ⚠️ Three scheduled jobs bypass the whole pipeline

`routes/console.php` still schedules three jobs that predate Stage 0 and share none of
its machinery — no catalogue, no `notification_deliveries`, no preferences, no
`RecipientResolver`:

- `GenerateMonthlyBusinessReportJob` — monthly, and the reason `report.monthly` has no
  email today. WhatsApp-only, so the chunk-3 PDF cannot ship until this is replaced.
- `ProcessSubscriptionLifecycleWhatsAppJob` — daily 07:00.
- `CheckNotificationsJob` — every six hours.

`GenerateMonthlyBusinessReportJob` also hardcodes a fallback recipient,
`'+256731794401'` (`app/Jobs/GenerateMonthlyBusinessReportJob.php:69`), so a business
with no phone on file has its monthly report sent to a hardcoded number. It sends
synchronously inside `foreach (Business::all())`, against §2.2 of
`whatsapp_implementation.md`.

**Wiring the catalogue triggers without retiring these sends customers two monthly
reports.** Decide what happens to them as part of Stage 4, not after. `StageZeroRepair
Test` covers the legacy `ProcessWhatsAppNotificationJob` path, so deletion is not
automatic.


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
- **#2 `businesses.timezone` column vs settings table.** Recommend column. Unanswered,
  and it blocks the monthly report job.
- **#6 Per-branch or consolidated monthly report.** Recommend consolidated business
  with a per-branch table inside the PDF.
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
- **`(array) $model` is not `$model->getAttributes()`.** Casting an Eloquent model to an
  array yields its private properties under NUL-prefixed mangled keys with the real
  columns buried inside `['attributes']`. A report built that way renders every figure as
  zero and nothing anywhere reports an error.

## Where the reasoning is written up

[`to-check.md`](to-check.md) — every deliberate deviation from the plan, the bugs found
during implementation, and the operational setup notes, stage by stage. Read it before
changing anything in here; several decisions look like bugs and are not.
