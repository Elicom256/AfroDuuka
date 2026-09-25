# Undone — Customer Communication (SES email + WhatsApp)

**Start here in the morning.** This is the working checklist for
[`email_whatsappp_notifications.md`](email_whatsappp_notifications.md). The plan is the
spec; this file is what is actually left to build. Finished items are kept and marked
✅ so progress is visible at a glance rather than inferred from a git log.

Last updated: end of Stage 2 chunk 2.

---

## Where we are

Stage 0, Stage 1 and the first two chunks of Stage 2 are done and green. The next
chunk is **Stage 2 chunk 3 (PDF attachments)**, then Stage 3, which is the first
genuinely new thing since chunk 2 — a real HTTP call to Meta instead of the
filesystem and log.

| Suite | Result |
|---|---|
| `tests/Feature/WhatsApp/` | ✅ 167 tests, 531 assertions |
| Full suite | 312 tests, 962 assertions, **3 failures** — all pre-existing and unrelated (see [Known failures](#known-failures)) |

---

## Start here tomorrow

### ⬜ Stage 2 · chunk 3 — PDF attachments

§5.6 and the §10 Email row both require a PDF on the monthly performance report and
the quotation email. Both currently render correctly as HTML with no attachment.

Judgement call already made: build this against **real** report and quotation data
rather than an empty payload, because the PDF layout is where a wrong column or a
missing total actually shows up.

- Decide the generator. The plan does not name one. `barryvdh/laravel-dompdf` is the
  obvious default; nothing in the repo has a PDF dependency yet.
- Attach to `report.monthly` and `quotation.sent` only. Not to transactional mail.
- `NotificationMail` already parameterises by type, so attachments should hang off the
  catalogue entry rather than a new subclass — the same reasoning that produced one
  Mailable instead of five.
- Test that the built MIME message really carries a `application/pdf` part, in the same
  spirit as the unsubscribe-header tests: assert on the message SES would receive, not
  on a `Mail::fake()` record.

---

## Remaining, in order

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
  `YYYY-MM`.
- Quotation send.

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
- **#4/#7 Catalogue scope.** `daily_sales_summary` and `payment_reminder` deferred;
  sale-receipt email deferred. The catalogue shipped **14** entries, but §4 of the plan
  still says "All 15" and §6.1 numbers from 15 — the plan's own count is stale and worth
  reconciling so the discrepancy is not rediscovered later.
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
  is harmless here.

## Two traps worth remembering

- **Never run `pint` unscoped.** Pointed at `app/` and `routes/` it reformatted **322**
  unrelated files. Everything was reverted; only the intended files were kept. Always
  pass explicit paths, then check `git status` afterwards.
- **`notification_recipients` is scoped by the global branch scope.** `->first()` in a
  test is ambiguous and silently returns the wrong tenant's row. Query by
  `business_id` + `address`, or `->sole()` on a known filter.

## Where the reasoning is written up

[`to-check.md`](to-check.md) — every deliberate deviation from the plan, the bugs found
during implementation, and the operational setup notes, stage by stage. Read it before
changing anything in here; several decisions look like bugs and are not.
