# DuukaFlow — Remaining heavy industry features (lightest → heaviest)

The quick wins (A1 notifications, A2 barcode, B2 attachments) are done. What's left are the heavy
P0/P2 builds. This doc orders them **lightest → heaviest** by implementation effort, with priority
and dependencies so value stays visible while you march through.

| #   | Feature                                       | Priority | Weight       | Roadmap / refactor           |
| --- | --------------------------------------------- | -------- | ------------ | ---------------------------- |
| 1   | Quotations / proforma invoices                | P0       | Medium       | §4 `C1` ✅ DONE (2026-09-24) |
| 2   | Customer communication (email and WhatsApp)   | P0       | Medium       | §4 `C3`                      |
| 3   | Automated payment / billing (self-renew)      | P1       | Heavy-medium | §4 `C2`                      |
| 4   | Finance as single source of truth             | P2       | Heavy        | §4 `D1`                      |
| 5   | Real payment collection (mobile money / card) | P0       | Heavy        | §4 `D2`                      |
| 6   | Payment reconciliation                        | P2       | Very heavy   | §4 `D4`                      |
| 7   | Warehouses / stock locations, serial & batch  | P2       | Very heavy   | §4 `D3` ✅ DONE              |

Notes:

- Each feature must ship with: backend tests (roadmap §6 advice), tenant-scoping checked
  (business + branch scopes everywhere), and its roadmap §5 row marked done.
- User decision carried over: leave the uninvoked policy stubs alone.

---

## 1. Quotations / proforma invoices — roadmap P0 (lightest of the heavy)

**Why:** the sales cycle in retail distribution is quote → accept → convert; DuukaFlow has no quotation
capability at all. High demo value, self-contained, standard workflow.

**Goal:** Draft → Sent → Accepted → Converted workflow; converting an accepted quote creates a `Sale`.

**Key pieces** (`refactor.md` §4 `C1`):

- New `Quotation` + `QuotationItem` models (business/branch-scoped, quote number, item snapshot of
  price/qty, validity, status enum, notes, discount/tax like sales).
- Routes CRUD + `accept` + `convert`, tenant-scoped; `convert` builds a `Sale` through the **same service
  used by POS checkout** so stock/cash-flow/ledger stay consistent.
- Quote number generator + PDF/download (reuse the existing receipt PDF pipeline).
- UI: Quotes list (status chips), editor mirroring the POS cart, detail with Send + Convert to Sale.
- Conversion idempotency (cannot double-convert).

**Deps:** none. Slot before Wave D so payments/finance can consume converted sales.

---

## 2. Customer communication (email/SMS/WhatsApp) — roadmap P0

**Why:** WhatsApp is **demo-mode** (`WHATSAPP_PROVIDER=demo`); no email/SMS. Digital receipts, reminders,
renewals and marketing are table stakes for the SaaS pitch.

**Goal:** real providers behind the existing `WhatsAppService` abstraction; add email; finish scheduled jobs.

**Key pieces** (`refactor.md` §4 `C3`):

- Provider adapters (WhatsApp Business API + email transport) behind one `NotificationChannel`
  interface; demo stays as fallback.
- Email template pipeline + unsubscribe/preferences.
- Wire the scheduled jobs to real providers: expiry, monthly report, low-stock, receipt-on-sale, quote send.
- Implement the `dedupe_key` column fix (roadmap §3.3) and preferences filtering.
- Flop `WHATSAPP_PROVIDER` to a real provider last, after test coverage.

**Deps:** no hard deps; needed by **C2** (renewal notifications) and desired by **#1** (quote send).

---

## 3. Automated payment / billing (subscriptions self-renew) — roadmap P1

**Why:** SaaS revenue needs recurring billing that renews on time; currently manual payment verification only.

**Goal:** subscriptions renew automatically with dunning/retry.

**Key pieces** (`refactor.md` §4 `C2`):

- Extend `Subscription` with auto-renew flag, next_billing_date, grace period; add `SubscriptionPayment`
  statuses (pending/paid/failed/succeeded-retry).
- Scheduled job on `next_billing_date`: charge via gateway, record payment, extend period, notify
  (uses **#2**); on failure → retry schedule + dunning notice.
- Webhook handler for gateway charge results (idempotent).
- UI: auto-renew toggle, billing history, retry button on failed.
- Structure the job to run in "manual verify" mode until the gateway lands.

**Deps:** needs **#2** (notify) + **#5** (gateway) for real charging.

---

## 4. Finance as single source of truth — roadmap P2

**Why:** `CashFlow` is currently the de-facto ledger; cleaner cash/bank reporting and reconciliation
need a real ledger.

**Goal:** `FinancialTransaction` ledger fed by all workflows; retire CashFlow duplication.

**Key pieces** (`refactor.md` §4 `D1`): new ledger model + writers wired into sales/purchases/expenses/
transfers/refunds; report queries migrate to it; keep CashFlow as read-only facade temporarily; cutover +
drop duplicates. Split into sub-tasks (spec in `todayswork.md`).

**Deps:** none; is the foundation for **#6**.

---

## 5. Real payment collection (mobile money / card) — roadmap P0

**Why:** retail POS must take MTN MoMo / Airtel Money / card. Manual verification cannot run a shop, and
it pairs with auto-billing (#3).

**Goal:** POS takes mobile money / card via a gateway.

**Key pieces** (`refactor.md` §4 `D2`): gateway adapter (1 mobile-money provider + card); POS split-payment
(already exists client-side) connects to gateway; webhook verification + reconciliation hooks with #4.

**Deps:** pairs with **#3** (recurring) and lays onto **#4**.

---

## 6. Payment reconciliation — roadmap P2 (very heavy)

**Why:** matches gateway statements to ledger entries — required for trustworthy money reporting.

**Goal:** auto-match gateway transaction statements against ledger entries.

**Key pieces** (`refactor.md` §4 `D4`): statement import, matching rules, exception queue. Layered on
**#4** + **#5**.

**Deps:** requires D1 + D2 first.

---

## 7. Warehouses / stock locations, serial & batch tracking — roadmap P2 (heaviest)

**Why:** needed for multi-location and FMCG/pharma compliance. Explicitly excluded from roadmap §2 MVP.

**Goal:** stock locations/warehouses and optional batch/serial tracing on products.

**Key pieces** (`refactor.md` §4 `D3`): new location model layer, stock movements target locations,
batch/serial fields + traceability UI. **Straightforward but large schema/scope change.**

**Deps:** none, but touches nearly every inventory screen.
