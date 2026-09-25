# To tighten

1. When a purchase is made, record an expense cayegorized as stock purchase 

## Deferred — found while wiring `email_whatsappp_notifications.md` (2026-09-26)

Recorded during Stage 0. Not part of today's plan; flagged for a later pass.

### Awaiting your sanction

1. **Masked secrets instead of removing them from the API.** `WhatsAppConfigController`
   now returns `********` for `access_token` / `webhook_verify_token` rather than
   omitting the fields, because `WhatsAppSettings.tsx` has *editable* inputs for both
   that round-trip through `GET /whatsapp`. Omitting them would make a plain save wipe
   the stored credential. A masked value on save is treated as "unchanged". The
   consequence: **the settings screen can no longer show an existing token.** If you
   want a proper reveal/rotate flow, that is a Stage 6 UI change.
2. **`webhook_verify_token` is encrypted as well as `access_token`.** The plan (§9) only
   asked for `access_token`. Both are provider credentials and the data migration was
   being written anyway, so both are now `encrypted` casts. Reverse if the extra
   column was deliberate.

### Pre-existing test failures (present before any of my changes)

Verified against `inventory_test` before starting, so none are regressions:

1. `Tests\Feature\NotificationTest::test_unread_count_returns_grouped_by_type`
   — **in the blast radius of Stages 1-4.** Worth fixing before the delivery log lands.
2. `Tests\Feature\POS\PosCheckoutTest::test_checkout_requires_auth`
3. `Tests\Feature\POS\PosSearchTest::test_search_requires_auth`
   — 2 and 3 both expect `302` but receive `200`, which smells like an auth-middleware
   difference in the test environment rather than a POS bug.

### Bugs found in passing, fixed under Stage 0 (noting the ones wider than the plan)

1. **`businesses.country_id` is `NOT NULL` but `BusinessService::create()` never set
   it.** Business creation was therefore broken for a *second* independent reason
   after the missing-import fix, so the plan's claim that Stage 0 "restores business
   registration" was not true on its own. Resolved server-side (Uganda, falling back
   to the first seeded country) so the existing registration form needs no change.
2. **`StoreWhatsAppConfigRequest` accepted a client-supplied `business_id`**, so any
   authenticated tenant could create a WhatsApp config against another business — the
   same class of hole the plan flagged on `update()` only.
3. **A template's `variables` column was being used as render data.** It holds variable
   *names* (`['product_name','current_stock']`), so messages rendered the literal text
   "product_name" instead of the value. Fixed in both call sites.
4. **`GenerateMonthlyBusinessReportJob` sent full monthly financials over WhatsApp**,
   which §2 rule 3 forbids ("never the numbers"). Reduced to the one-line nudge that
   catalogue row #9 specifies; the PDF-by-email half lands in Stage 2.
5. **`queue:work` was scheduled under `schedule:run` and nothing else drained the
   queue.** `QUEUE_CONNECTION=database`, and there was no supervisor config or compose
   worker anywhere, so removing that line as the plan instructed would have queued every
   notification into a queue nobody read. Added a real `queue-worker` service to
   `docker-compose.dev.yml` before removing it. **Worth confirming you want that service
   in the dev stack** rather than supervisor.

### Environment problems found (not fixed)

1. **`phpunit.xml` points at `DB_DATABASE=inventory`, not `inventory_test`.** The
   `inventory_test` database already exists, so this looks like a misconfiguration —
   and a dangerous one, because `RefreshDatabase` runs `migrate:fresh`. **Running the
   Feature suite as configured would drop all 85 tables of your dev data.** I have been
   running everything with `DB_HOST=127.0.0.1 DB_DATABASE=inventory_test` and have not
   changed the file. This should be fixed before anyone else runs the suite.
2. **`.env` sets `DB_HOST=pgsql`**, which only resolves inside the Docker network, so
   `php artisan` commands fail from the host. I have been prefixing `DB_HOST=127.0.0.1`.
3. **PHP emits a startup warning on every command**: `Unable to load dynamic library
   'mysqli.so' (undefined symbol: mysqlnd_global_stats)`. Harmless — the project is on
   `pgsql` — but it pollutes every command's output.

### Bugs found while building Stage 1 (schema + models), fixed

1. **`EffectiveBranchScope` made every business-wide row invisible to its own tenant.**
   The scope ended in `whereIn('business_branch_id', $branchIds)`, and `whereIn` never
   matches NULL. Any row stored business-wide — `notification_recipients` with no branch,
   `notification_deliveries` for a subscription or payment notification — was therefore
   filtered out even for its own business, while a neighbouring business's rows were not.
   In practice a business saw an empty delivery history. The scope now matches branch rows
   OR `business_branch_id IS NULL`, grouped so it still ANDs with the business scope.
   **This was systemic, not specific to notifications**: it applies to every table that
   carries a nullable `business_branch_id`, so it silently affected existing screens too.
   There is a companion `whereRaw('1 = 0')` for a business with zero branches, which had
   the same problem; that is now restricted to branch rows only.
2. **`notification_deliveries.recipient_address` was NOT NULL**, which is wrong for this
   table. Its purpose is to record sends that never happened, including `no_recipient`
   suppressions, which have no address to snapshot. Made nullable.
3. **`notification_subscriptions.token` was NOT NULL with a `''` seed on create**, so the
   second distinct address collided with the first on the unique index. Made nullable:
   Postgres permits any number of NULLs under a unique index, so "at most one row per
   issued token" still holds while un-mailed addresses can exist. Also switched
   `firstOrCreate` to `createOrFirst` so two concurrent requests for the same brand new
   address do not fail on the email unique index, and made `email` itself unique.
4. **`NotificationRecipient::isActive()` answered false for a valid row.** `create()` does
   not populate column defaults in memory, so a freshly created recipient reported
   `is_active = null` until refreshed — the resolver would have skipped a good recipient
   and logged it as `no_recipient`. Fixed with a `$attributes` default block.

### Migration convention decided with you (2026-09-26)

- **Column additions are folded into the original create migration**, not added as a new
  `add_*_to_*` migration. Four such migrations were deleted and their columns merged into
  `2026_01_01_000002_create_businesses_table` (timezone),
  `2026_01_01_000007_create_business_branch_products_table` (alert_state, alert_episode)
  and `2026_08_21_000001_create_whatsapp_configs_table` (six Meta template fields, plus the
  `business_id` unique index). The survivors were renumbered to remove the gaps.
- Five migrations remain, because they create new tables or move data and so cannot be
  folded: the two create-table migrations for notifications, the SES-style secret
  backfill, the legacy `whats_app_message_logs` backfill, and the recipients table.
- **Consequence you need to decide on:** your dev database `inventory` has already run the
  edited migrations, so Laravel will never re-apply them. It is currently missing
  `businesses.timezone`, `products.alert_state`, `products.alert_episode`, the six
  `whats_app_templates` Meta fields, the `whats_app_configs` unique index, and the three
  new notification tables. I have not touched it. Either `migrate:fresh` the dev database
  or let me apply the additive `ALTER TABLE`s for you. No duplicate
  `whats_app_configs.business_id` rows exist, so the unique index will apply cleanly.

### Stage 1 chunk 4 (config, channel contract, renderer) — findings

- **The seeded `whats_app_templates` rows cannot dispatch as they stand.**
  `WhatsAppService::ensureTemplatesForBusiness()` seeds `low_stock_alert` and
  `payment_reminder` with `'status' => 'approved'` (lowercase, our own wording-status
  column) and no `provider_name`, `language_code` or `template_status`. But approval is
  now read from `template_status === 'APPROVED'`, mirroring Meta. So every existing
  template row reads as not approved and **every WhatsApp notification would be
  suppressed as `template_not_approved`** until those rows are backfilled from Meta's
  `GET /{waba-id}/message_templates`. The two seeded names are also the old shape and do
  not match the new catalogue keys (`inventory.low_stock` rather than `low_stock_alert`).
  This is expected — the rows are placeholders, not real Meta approvals — but it means
  template seeding has to be rewritten to the catalogue's `meta` names before the
  dispatcher is useful, and that is the next piece of work rather than a surprise later.
- **Two existing callers still use the lenient renderer.** `ProcessWhatsAppNotificationJob`
  and `GenerateMonthlyBusinessReportJob` call `WhatsAppTemplateService::render()`, which
  is now a documented back-compat wrapper that cannot enforce declared variable order,
  because it is handed a data array with no declared order. Both are scheduled/queued
  paths that will be replaced by the dispatcher, so this is temporary and safe, but
  neither should be treated as correct rendering until it is.

### Stage 1 chunk 5 (template provisioning) — findings

- Template rows are now provisioned from `config('notifications.catalogue')` by
  `App\Services\Notifications\TemplateProvisioner`, seeded per business by
  `Database\Seeders\WhatsAppTemplateSeeder`. 13 WhatsApp rows per business; the
  email-only `quotation.sent` correctly gets none.
- `WhatsAppTemplate.name` is now the **notification type** (`inventory.low_stock`), and
  the Meta name moved to `provider_name` (`low_stock_alert`). Anything still matching
  templates on the old name or on `category` will silently stop resolving. I replaced
  `WhatsAppService::ensureTemplatesForBusiness()`, which is the only caller, but this is
  worth a grep before anything else touches template lookup.
- **Re-provisioning deliberately does not touch `template_status`, `body` or
  `variables`.** `template_status` mirrors Meta and is only written by the sync command
  (still to come — `duukaflow:whatsapp:sync-templates`); the body and its ordered
  `variables` are owner-editable, so a re-run must not revert their wording. Only a
  *null* `provider_name` / `language_code` is backfilled, because a wrong non-null value
  there is how a send starts failing on a parameter mismatch.
- **Seeded rows are `PENDING`, never `APPROVED`.** Nothing is approved until Meta says
  so, and fabricating an approval would disable the only guard that stops us sending
  against an unapproved template. Consequence: with a real Meta provider, every
  notification is suppressed as `template_not_approved` until the sync command runs.
  A test asserts the seeder can never produce an approved row.
- `pruneOrphans()` deletes only rows with a **null `provider_name`** that match no
  catalogue type — i.e. the old hand-written `low_stock_alert` / `payment_reminder`
  seeds, which could never be dispatched. A row carrying a real Meta template is never
  deleted even if the catalogue moves on, since history may reference it.
- The `{{token}}` bodies and their ordered `variables` live in `TemplateProvisioner`
  as a constant. A test asserts every seeded body's placeholders match its declared
  `variables` exactly, and that each one renders clean, so wording cannot drift out of
  sync with its positional contract.
