# Job Tenant Context

## Problem
`checked.md` §3: "Jobs never wrap work in `BusinessContext::run()`." Noted as a footgun
rather than a defect — all five jobs filter on `business_id` explicitly in every query.

## Task
Audit the five jobs and put each one in its tenant, without breaking anything that works.
Read rules.md.

## Status — done

## The finding: it is five different shapes, not one problem

"Audit the jobs and wrap them" reads like one edit. It is three, and getting two of them
wrong would have caused silent data loss rather than a test failure. That is what most of
this file is about.

| Job | Shape | The tenant comes from | Where the wrap goes |
|---|---|---|---|
| `CheckNotificationsJob` | platform sweep | each `$business` in the loop | **inside** the loop |
| `ProcessSubscriptionLifecycleWhatsAppJob` | platform sweep | each `$subscription->business` | **inside** the loop |
| `ProcessWhatsAppNotificationJob` | one business | `$payload['business_id']` | one wrap |
| `SendNotificationJob` | one business | `$delivery->business_id` | one wrap, after the row loads |
| `ProcessSesSuppressionsJob` | system-wide by design | nowhere | **none — deliberately** |

### Why the two sweeps must wrap inside the loop

`CheckNotificationsJob::businessesToCheck()` returns *every* business with an Executive, and
`ProcessSubscriptionLifecycleWhatsAppJob` queries `Subscription` with no `business_id`
predicate at all. Both are platform sweeps by design. A single `run()` around `handle()`
would AND one tenant onto the sweep and process exactly that tenant — leaving every other
business silently unalerted, with the job reporting success and the tests green.

That is a nastier failure than the bug this set out to remove: the existing queries are
correct, so nothing was ever going to catch it. Both jobs now carry a test that pins the
wrap to each row, and the lifecycle job's candidate query is explicitly
`withoutGlobalScopes()` so the sweep cannot be narrowed by accident from the outside.

### Why the SES job must not be wrapped

It is the backstop for sends the provider never confirmed. `markFailed()` forceFills and
saves an already-loaded model, which re-queries nothing, so no scope is applied and no
context is needed. Wrapping `handle()` would narrow a platform-wide backstop to one tenant
while reporting that it settled the sends. The class docblock now says this, and says what
to do instead if it ever gains a per-row write.

### Why the row loads before the wrap

`SendNotificationJob` reads its delivery with `withoutGlobalScopes()`. That is deliberate and
correct — the row *is* the thing that names the tenant, so scoping the lookup by a context
would be circular. It also means the job was already tenant-correct before this change: the
send is driven by `$delivery->business_id`, and two existing tests already assert that a
deactivated config on one tenant does not affect another. The wrap is there so the attachment
builders and any query added later stay inside the delivery's tenant.

## Also corrected

- `BusinessContext`'s own docblock documented `BusinessContext::run($id, fn () => ...)`. That
  static spelling does not exist — `run()` is an instance method with no `__callStatic`, so
  following the docblock throws. It now shows the container form and says why.
- `ProcessWhatsAppNotificationJob` had the same recipient guard twice, ~50 lines apart, with
  near-identical comments. The second is unreachable — the first returns on an empty
  recipient. Removed.
- It also accepted a payload with no `business_id` and would have run the whole send inside
  `run(0, ...)`. Now refused up front with a log line.

## What this is not

**Not a fix.** Every query in all five jobs already named `business_id`, and no test
observation changes as a result. This is what makes the *next* query correct by
construction.

The clearest case is `Product`, which has no `business_id` column at all — the tenant there
rests entirely on `EffectiveBranchScope`, which applies no constraint whatsoever when there
is neither an authenticated user nor a context. One new line added to a low-stock check
without that filter would read every tenant's products, and nothing would say so. The test
`test_a_product_query_without_a_business_filter_is_scoped_by_the_context` exists for exactly
that, and asserts the query is `Product::count()` and *not* `withoutGlobalScopes()` — the
latter would strip the scope under test and return 2 either way, proving nothing.

## Tests
`api/tests/Feature/Jobs/ScheduledJobTenantScopeTest.php` — 11 tests, both jobs previously
had **zero** coverage.

A `BusinessContext` subclass records every `run()` it is asked to perform, so the suite can
assert on the wrap itself rather than only on the outcome. That distinction is the whole
point: observing behaviour alone cannot tell "scoped by context" from "scoped because the
query happened to name `business_id`" — the outcome is identical, which is why these bugs
can sit unseen.

Coverage: low-stock alerts reach only the owning business's admin; the sweep enters each
business; an unfiltered product query is scoped by context; an overdue customer of another
business is never reported; a lapsed subscription queues a message for its own business and
only for businesses that own one; the lifecycle sweep enters each business; a broken wrap
would narrow the sweep (shown by running the candidate query inside a context and asserting
it sees one tenant); a business with no phone is skipped; and no context survives either job.

### Mutation-checked
Three mutations, each caught:

1. Hoist `CheckNotificationsJob`'s wrap out of the loop → caught.
2. Delete `CheckNotificationsJob`'s wrap entirely (the pre-change state) → caught.
3. Wrap the lifecycle sweep once around `handle()` instead of per row → caught.

All three were caught by the context-shape test, and **none** by the behavioural assertions.
That is the honest result and it is why the shape is asserted directly: these jobs were
already correct, so no behavioural test could have distinguished the two states. A suite of
only outcome assertions here would have looked green through all three regressions.

## Verification
- `docker compose exec -T backend php artisan test` → **774 passed**, 2268 assertions
- `docker compose exec -T backend ./vendor/bin/pint --test` → 802 files, clean
- Frontend untouched; `cd ui && npx vitest run` → 756 passed

## Also noticed, not changed

**The scheduler may not be running at all.** The three scheduled jobs are declared in
`routes/console.php`, but there is no `schedule:run` cron and no scheduler service in either
compose file — the only cron under `ops/` is the database backup. If that is right, all
three of these jobs have never fired in the deployed environment, and this file has been
hardening code that was never reached. Worth confirming before building anything further on
top of it. Added to `checked.md` §3 as an open item.
