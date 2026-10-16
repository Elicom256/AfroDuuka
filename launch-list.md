# Launch Integration Checklist — External Service Integrations

Ordered by **launch criticality** (most essential → least essential). All are external services requiring provider accounts, API credentials, and subscription costs.

---

## 1. Payment Aggregator — Flutterwave (Primary Gateway)

**What:** Card payments (Visa/Mastercard), mobile money collections (MTN MoMo, Airtel Money, M-Pesa), bank transfers, and alternative payment methods for online subscriptions and high-value POS transactions. Covers **Uganda, Kenya, Tanzania, Rwanda, Nigeria, Ghana, South Africa** and 30+ African countries.

**Why:** Single integration for **pan-African payments** — no need for separate MTN/Airtel contracts per country. Flutterwave handles mobile money collections via their aggregator model (3% fee). Enables subscription auto-pay via card, B2B/foreign cards, and cross-border expansion without new integrations. `api/README.md` lists Flutterwave.

**How:**
- Provider: **Flutterwave** (recommended for East Africa + continental coverage)
- `payment_gateways` table supports `flutterwave` provider
- Flow: Redirect to provider hosted checkout → webhook confirms → activate subscription/record sale
- Webhook: Verify signature, idempotent on `tx_ref`
- Mobile money: Customer enters phone → receives USSD push → enters PIN → instant confirmation
- **No direct MTN/Airtel contracts needed** — Flutterwave manages aggregator relationships

**When:** **Pre-launch (P0)** — single integration unblocks payments across all target markets.

**Cost:**
- Flutterwave Uganda: **3.2%** per local card transaction; **4.8%** for international cards; **3.0%** for mobile money collections
- Kenya/TZ/RW/NG: Similar card rates; mobile money ~2.5–3.5%
- Bank payouts: **UGX 5,000** per transfer; Mobile money payouts: **UGX 1,000** (≤125k) or **1.2%** (>125k)
- Estimated: **$50–300/month** depending on card/mobile money volume.
- **No monthly minimums**, no setup fees.

---

## 2. Email Delivery — Resend (Free Tier → SES Migration)

**What:** Document-shaped communication: account verification emails, PDF receipts, monthly reports, subscription invoices, password resets, login alerts, and compliance records.

**Why:** **Must work before WhatsApp** — email is the fallback for all notification types, carries attachments/tables (PDF receipts, reports), handles password resets (Laravel default), and provides audit trail. Resend free tier (3,000 emails/month) covers MVP; migrate to Amazon SES at scale for cost optimization.

**How:**
- Provider: **Resend** (start) → **Amazon SES** (scale)
- Resend: `composer require resend/resend-php`, `ResendMailChannel`, Blade Mailables, `LogMailChannel` for dev
- SES migration: Swap mail driver, same Mailable classes, `SesMailChannel`
- Channel strategy: Email for documents/summaries/attachments; WhatsApp for urgent nudges only
- `notification_preferences` table for opt-in/opt-out, unsubscribe token
- **Account verification** (signup → onboarding complete → verify): `BusinessRegistered` → `BusinessVerification` Mailable → `GET /api/verify-email` → business `active`. Full plan in `account.md`

**When:** **Pre-launch (P0)** — receipts, reports, password resets, compliance, fallback for WhatsApp.

**Cost:**
- **Resend Free Tier**: 3,000 emails/month, 1,000 contacts — **$0/month**
- **Resend Pro**: $20/month for 50,000 emails, then $0.10/1,000
- **Amazon SES** (migration target): **$0.10 per 1,000 emails** + $0.12/GB attachment bandwidth
- Estimated at scale (SES): **$5–20/month** for 50k–200k emails/month.
- No monthly minimum on either; pay-per-use.

---

## 3. Offline-First Sync (Service Worker + IndexedDB)

**What:** Continue selling during internet outage; sync when online. Queue POS sales, inventory updates, customer creates locally; background sync on reconnect.

**Why:** **Critical for African businesses** — internet reliability is inconsistent across Uganda/East Africa. `future.md` §5: "If internet dies: continue selling, sync later." `checked.md` line 67: "POS is not offline-capable. Retrofitting sync after data exists is far costlier." Build **early**, not as retrofit.

**How:**
- Frontend: Service Worker + IndexedDB (Workbox), queue mutations, background sync on `online` event
- Backend: Idempotent endpoints, conflict resolution (last-write-wins with server authority), sync status endpoint
- Queue: `sync_pending` table, `SyncPendingJob` on reconnect
- Prioritize: Sales → Inventory → Customers (revenue-critical first)

**When:** **Pre-launch (P0)** — architectural foundation, not a feature. Ship with MVP.

**Cost:**
- Dev effort: **3–6 weeks** for robust implementation
- No external subscription cost

---

## 4. WhatsApp Business API (Meta Cloud API)

**What:** Transactional WhatsApp notifications for subscription events, inventory alerts, order confirmations, monthly report nudges, receipt delivery nudges.

**Why:** High read rate (~95%); product promise for subscription expiry reminders, low-stock alerts, order notifications. **Email handles documents; WhatsApp handles nudges.** Meta 24-hour service window forces template-only outbound.

**How:**
- Provider: **Meta Cloud API** (WhatsApp Business Platform) — direct control, no BSP markup if self-hosted
- Built: `WhatsAppProviderInterface` + a real `MetaWhatsAppProvider` (`sendMessage` with per-outcome delivery classification, paginated `listTemplates`, one pinned Graph API version), `DemoWhatsAppProvider`, `WhatsAppConfig`, `WhatsAppTemplate`, `WhatsAppMessageLog`, `ProcessWhatsAppNotificationJob`, the `afroduuka:whatsapp:sync-templates` command, the config/templates/logs endpoints, and provider tests
- Need: inbound **delivery-status webhook** `POST /api/webhooks/whatsapp` to advance `WhatsAppMessageLog` through sent → delivered → read → failed (and stop the `sending` rows Meta answers on), plus dedupe on `dedupe_key` (unique index). No template-submission API — approval stays a manual step in Meta Business Manager.
- Channel strategy: WhatsApp for urgent/state-change (≤4 lines); Email for documents/summaries
- Template categories: `UTILITY` (subscription, inventory, orders); Monthly report nudge as `UTILITY` not `MARKETING`

**When:** **Pre-launch (P0)** — product promise, subscription expiry reminders, inventory alerts.

**Cost:**
- **Per-message pricing** (since July 2025; Meta charges per delivered template message, not per 24h conversation):
  - `SERVICE` (customer-initiated 24h window): **Free** (unlimited since Nov 2024)
  - `UTILITY` templates within open service window: **Free**
  - `UTILITY` templates outside service window: **~$0.004/message** (US rate; Uganda rate varies)
  - `MARKETING`: **~$0.025/message** (US rate; no volume discounts)
  - `AUTHENTICATION` (OTP): **~$0.004/message** (US rate)
- **Free entry points**: Click-to-WhatsApp ads open 72h free window for all categories
- Estimated: **$10–50/month** for 2,000–10,000 utility messages at scale (mostly within service windows).
- **One-time**: Template approval in Meta Business Manager (1–3 days per template).
- **Note**: BSP markup typically adds $0.003–$0.01/message if using a BSP; self-hosted Cloud API avoids this.

---

## 5. URA EFRIS (e-Invoicing / Fiscalisation)

**What:** Real-time fiscalisation of sales receipts to Uganda Revenue Authority (e-invoicing compliance). Every completed sale must be fiscalised; duplicate fiscalisation number = compliance event.

**Why:** Legal requirement for Ugandan businesses issuing tax invoices. Non-compliance = penalties. `checked.md` line 43: "retry is unverified — duplicate fiscalisation number is a compliance event, not a UI glitch."

**How:**
- Provider: **URA EFRIS API** (requires taxpayer registration, EFRIS client certificate)
- Integration: `SaleService` → after commit, queue `FiscaliseSaleJob` → POST to URA `/efris/invoice` → store `fiscalisation_number`, `fiscalised_at`, `ura_response` on `Sale`
- Retry: Exponential backoff, max 3 attempts, dead-letter to manual review
- Duplicate handling: Idempotency key = `sale_id`; URA returns existing fiscalisation if re-sent
- Testing: URA sandbox environment (separate cert/credentials)

**When:** **Pre-launch (P0 for Uganda)** — legal compliance, cannot operate without it in Uganda.

**Cost:**
- **No per-transaction fee** (government service)
- **Operational**: Certificate management, sandbox/prod cert rotation, monitoring for URA downtime
- **Dev effort**: ~1–2 weeks for integration + sandbox testing + certificate management

---

## 6. Direct Mobile Money (MTN MoMo + Airtel Money) — Post-Launch

**What:** Direct real-time mobile money collection via **MTN MoMo API** + **Airtel Money API** (bypassing Flutterwave aggregator). Covers ~95% of Uganda mobile money market.

**Why:** **Lower per-transaction cost** (~1.5–2.5% vs Flutterwave's 3% mobile money fee) at scale. But requires **heavy paperwork**: business registration, separate contracts per provider, KYC, URA compliance, 3–6 month onboarding per provider. **Not viable pre-launch.**

**How:**
- Provider: **MTN MoMo API** (via MTN Developer Portal) + **Airtel Money API** (via Airtel Developer Portal)
- Integration: `payment_gateways` table supports `mtn_momo`, `airtel_money` providers, `PaymentGatewayController` CRUD with role gates
- Flow: Initiate payment → redirect to provider USSD/app → webhook confirms → update sale/subscription
- Webhook: `POST /api/webhooks/mtn-momo`, `POST /api/webhooks/airtel-money` (need implementation)
- Queue jobs for idempotent retries, dedupe on `transaction_id`

**When:** **Post-launch (~1 year, P2)** — after Flutterwave is live and generating revenue, when volume justifies direct contracts.

**Cost:**
- MTN MoMo: **Transaction fee ~1.5–2.5%** per successful payment (negotiated per volume). No monthly fee for sandbox; production requires business registration.
- Airtel Money: **Transaction fee ~1.5–2.5%** per successful payment. Similar model.
- Estimated savings at scale: **~UGX 500k–2M/month** vs Flutterwave aggregator fee at 1,000–5,000 transactions.
- **Hidden cost**: 3–6 months per provider for contract negotiation + compliance.

---

## Summary — Priority Order & Timeline

| # | Integration | Criticality | When | Est. Monthly Cost |
|---|-------------|-------------|------|-------------------|
| 1 | **Flutterwave (Pan-African Payments)** | P0 — Single integration for all markets | Pre-launch | $50–300 |
| 2 | **Email (Resend → SES)** | P0 — Receipts, resets, fallback | Pre-launch | $0–20 |
| 3 | **Offline-First Sync** | P0 — Architecture, not feature | Pre-launch | Dev effort only |
| 4 | **WhatsApp (Meta Cloud API)** | P0 — Product promise, nudges | Pre-launch | $10–50 |
| 5 | **URA EFRIS** | P0 — Legal compliance (Uganda) | Pre-launch | Dev effort only |
| 6 | **Direct MTN/Airtel MoMo** | P2 — Cost optimization at scale | ~1 year post-launch | 1.5–2.5% (vs 3%) |

---

## Implementation Notes

1. **Start with Flutterwave** — single integration unblocks payments across Uganda, Kenya, Tanzania, Rwanda, Nigeria, Ghana, SA. No per-country contracts.
2. **Parallel**: Email (Resend) + Offline Sync (architectural) — both P0, different teams.
3. **WhatsApp** after Email is working — Email is the fallback; WhatsApp is the nudge layer.
4. **URA EFRIS** can run in parallel (different team) — sandbox testing first, Uganda-only.
5. **Direct MTN/Airtel** only after ~1 year when volume justifies contract overhead — Flutterwave aggregator fee (3%) is the cost of speed-to-market.
6. **Offline sync** is architectural — plan early, build early (retrofitting is expensive per `checked.md`).

---

## Provider Account Setup Checklist

| Provider | Account Needed | Docs | Sandbox | Prod Go-Live |
|----------|----------------|------|---------|--------------|
| Flutterwave | Business registration | developer.flutterwave.com | Yes | KYC + settlement account |
| Resend | GitHub/Email signup | resend.com/docs | Free tier (3k/mo) | Auto |
| Meta WhatsApp | Meta Business Manager, WhatsApp Business Account | developers.facebook.com/docs/whatsapp | Yes (test numbers) | Template approval (1–3 days) |
| URA EFRIS | Taxpayer registration, EFRIS cert | URA portal | Yes (test env) | Live cert from URA |
| Amazon SES | AWS Account | docs.aws.amazon.com/ses | Yes (sandbox) | Production access request |
| MTN MoMo (later) | Business registration, MoMo Developer Portal | developer.mtn.com | Yes | Business cert + KYC |
| Airtel Money (later) | Business registration, Airtel Developer Portal | developers.airtel.africa | Yes | Business cert + KYC |

---

> **Note:** All secrets (API keys, tokens, certificates) must be stored in `.env` / deployment secrets, **never committed**. Rotate any exposed credentials immediately (see `checked.md` lines 26–28).
