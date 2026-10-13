# Launch Integration Checklist — External Service Integrations

Ordered by **launch criticality** (most essential → least essential). All are external services requiring provider accounts, API credentials, and subscription costs.

---

## 1. Mobile Money Payment Gateway (MTN MoMo + Airtel Money)

**What:** Real-time mobile money collection for POS sales, subscription auto-renewal, and customer payments. Supports **MTN MoMo** and **Airtel Money** (covers ~95% of Uganda mobile money market).

**Why:** ~90% of Ugandan retail transactions are mobile money. Cash-only POS cannot be sold as production SaaS. Enables subscription auto-collection (SaaS revenue), POS checkout, and customer debt collection.

**How:**
- Provider: **MTN MoMo API** (via MTN Developer Portal) + **Airtel Money API** (via Airtel Developer Portal)
- Integration: `payment_gateways` table already exists (provider: `mtn_momo`, `airtel_money`), `PaymentGatewayController` CRUD with role gates
- Flow: Initiate payment → redirect to provider USSD/app → webhook confirms → update sale/subscription
- Webhook: `POST /api/webhooks/mtn-momo`, `POST /api/webhooks/airtel-money` (need implementation)
- Queue jobs for idempotent retries, dedupe on `transaction_id`

**When:** **Pre-launch (P0)** — largest commercial gap per `checked.md` line 48. Cannot sell SaaS without it.

**Cost:**
- MTN MoMo: **Transaction fee ~1.5–2.5%** per successful payment (negotiated per volume). No monthly fee for sandbox; production requires business registration.
- Airtel Money: **Transaction fee ~1.5–2.5%** per successful payment. Similar model.
- Estimated: **UGX 500k–2M/month** at 1,000–5,000 transactions (scales with volume).

---

## 2. WhatsApp Business API (Meta Cloud API)

**What:** Transactional WhatsApp notifications for subscription events, inventory alerts, order confirmations, monthly report nudges, and receipt delivery.

**Why:** Product promise advertises WhatsApp features; high read rate (~95%); required for subscription expiry reminders, low-stock alerts, order notifications, monthly report nudges. Meta **24-hour service window** forces template-only messages.

**How:**
- Provider: **Meta Cloud API** (WhatsApp Business Platform) — recommended over 360dialog/WATI for direct control
- Already: `WhatsAppProviderInterface`, `MetaWhatsAppProvider` (stub), `WhatsAppConfig`, `WhatsAppTemplate`, `WhatsAppMessageLog`, `ProcessWhatsAppNotificationJob`, `DemoWhatsAppProvider`
- Need: Complete `MetaWhatsAppProvider` HTTP client, webhook `POST /api/webhooks/whatsapp` for delivery status, template approval flow, dedupe on `dedupe_key` (unique index)
- Channel strategy: WhatsApp for urgent/state-change (≤4 lines); Email for documents/summaries (see `email_whatsappp_notifications.md` §2)
- Template categories: `UTILITY` (subscription, inventory, orders); Monthly report nudge as `UTILITY` not `MARKETING`

**When:** **Pre-launch (P0)** — product promise, subscription expiry reminders, inventory alerts.

**Cost:**
- **Per-conversation pricing** (Meta bills per 24h session started by template):
  - `UTILITY`: ~$0.005–0.01/conversation (first 1,000 free/month)
  - `MARKETING`: ~$0.03–0.05/conversation (avoid in MVP)
  - `AUTHENTICATION`: ~$0.01/conversation
- Estimated: **$20–100/month** for 2,000–10,000 conversations at scale (mostly `UTILITY`).
- **One-time**: Template approval in Meta Business Manager (1–3 days per template).

---

## 3. Email Delivery (Amazon SES)

**What:** Document-shaped communication: PDF receipts, monthly reports, subscription invoices, password resets, login alerts.

**Why:** WhatsApp cannot carry attachments/tables; email is ~$0.10/1k emails (SES); required for PDF receipts, monthly reports, compliance records, password resets (Laravel ships this).

**How:**
- Provider: **Amazon SES** (recommended for cost/deliverability) or Mailgun/Postmark
- Need: `composer require aws/aws-sdk-php`, `SesMailChannel`, `SendEmailNotificationJob`, Blade Mailables for receipts/reports, `LogMailChannel` for dev
- Channel split: Email for documents/summaries; WhatsApp nudge only ("your report is ready")
- `notification_preferences` table for opt-in/opt-out, unsubscribe token

**When:** **Pre-launch (P0)** — PDF receipts, monthly reports, password resets, compliance.

**Cost:**
- **Amazon SES**: **$0.10 per 1,000 emails** + $0.12/GB attachment bandwidth
- Estimated: **$5–20/month** for 50k–200k emails/month at scale.
- No monthly minimum; pay-per-use.

---

## 4. URA EFRIS (e-Invoicing / Fiscalisation)

**What:** Real-time fiscalisation of sales receipts to Uganda Revenue Authority (e-invoicing compliance). Every completed sale must be fiscalised; duplicate fiscalisation number = compliance event.

**Why:** Legal requirement for Ugandan businesses issuing tax invoices. Non-compliance = penalties. `checked.md` line 43: "retry is unverified — duplicate fiscalisation number is a compliance event, not a UI glitch."

**How:**
- Provider: **URA EFRIS API** (requires taxpayer registration, EFRIS client certificate)
- Integration: `SaleService` → after commit, queue `FiscaliseSaleJob` → POST to URA `/efris/invoice` → store `fiscalisation_number`, `fiscalised_at`, `ura_response` on `Sale`
- Retry: Exponential backoff, max 3 attempts, dead-letter to manual review
- Duplicate handling: Idempotency key = `sale_id`; URA returns existing fiscalisation if re-sent
- Testing: URA sandbox environment (separate cert/credentials)

**When:** **Pre-launch (P0 for Uganda)** — legal compliance, cannot operate without it.

**Cost:**
- **No per-transaction fee** (government service)
- **Operational**: Certificate management, sandbox/prod cert rotation, monitoring for URA downtime
- **Dev effort**: ~1–2 weeks for integration + sandbox testing + certificate management

---

## 5. Payment Aggregator (Flutterwave / Pesapal)

**What:** Card payments (Visa/Mastercard), bank transfers, and alternative payment methods for online subscriptions and high-value POS transactions.

**Why:** Mobile money covers retail POS; card/online needed for B2B, foreign cards, subscription auto-pay via card. `api/README.md` lists Flutterwave and Pesapal.

**How:**
- Provider: **Flutterwave** (recommended for Uganda/Kenya/NG) or **Pesapal**
- `payment_gateways` table supports `flutterwave`, `pesapal` providers
- Flow: Redirect to provider hosted checkout → webhook confirms → activate subscription/record sale
- Webhook: Verify signature, idempotent on `tx_ref`

**When:** **Post-launch (P1)** — mobile money covers 95% of retail; card is for B2B/online.

**Cost:**
- Flutterwave: **~1.4% + UGX 500** per local card transaction; **3.8%** for international cards
- Pesapal: **~2.5–3%** per transaction
- Estimated: **$50–200/month** depending on card volume.

---

## 6. SMS Gateway (Twilio / Africa's Talking / Infobip)

**What:** Fallback for WhatsApp delivery failures, OTP for login, critical alerts when WhatsApp is down.

**Why:** WhatsApp quality-score collapse risk; business-level failover for SMS/email if WhatsApp down (per `future.md` line 149). OTP for 2FA/login alerts.

**How:**
- Provider: **Africa's Talking** (best Uganda coverage), **Twilio**, or **Infobip**
- Channel: `sms` in `notification_preferences`, fallback logic in `NotificationDispatcher`
- Use case: OTP, WhatsApp failover, critical alerts only (not marketing)

**When:** **Post-launch (P1/P2)** — failover/OTP, not core MVP.

**Cost:**
- Africa's Talking: **~UGX 30–50/SMS** (local); **~$0.01–0.03/SMS** international
- Twilio: **$0.0075/SMS** (Uganda)
- Estimated: **$10–50/month** for OTP + failover volume.

---

## 7. Thermal Printer / Barcode Scanner Integration

**What:** Local hardware integration for POS receipt printing and barcode scanning.

**Why:** Retail POS requires physical receipt printing and barcode scanning. `api/README.md` lists as critical.

**How:**
- **Thermal Printer**: ESC/POS over Bluetooth/USB/Network (e.g., Epson TM-m30, Star TSP100). Driver: `mike42/escpos-php` or direct ESC/POS bytes.
- **Barcode Scanner**: HID keyboard wedge (acts as keyboard input) — no driver needed; or USB/Bluetooth serial.
- Integration: POS component detects scanner input, prints via `escpos-php` to paired printer.

**When:** **Pre-launch (P0)** for physical retail pilot.

**Cost:**
- Hardware: **UGX 500k–2M** per terminal (printer + scanner)
- Software: Open-source libraries, no subscription

---

## 8. Offline-First Sync (Service Worker + IndexedDB)

**What:** Continue selling during internet outage; sync when online.

**Why:** `future.md` §5: "If internet dies: continue selling, sync later." `checked.md` line 67: "POS is not offline-capable. Retrofitting sync after data exists is far costlier."

**How:**
- Frontend: Service Worker + IndexedDB (Workbox), queue mutations, background sync on `online` event
- Backend: Idempotent endpoints, conflict resolution (last-write-wins with server authority), sync status endpoint
- Queue: `sync_pending` table, `SyncPendingJob` on reconnect

**When:** **Post-launch (P1)** — critical for retail reliability, but complex retrofit.

**Cost:**
- Dev effort: **3–6 weeks** for robust implementation
- No external subscription cost

---

## Summary — Priority Order & Timeline

| # | Integration | Criticality | When | Est. Monthly Cost |
|---|-------------|-------------|------|-------------------|
| 1 | **MTN MoMo + Airtel Money** | P0 — Launch blocker | Pre-launch | UGX 500k–2M / $150–600 |
| 2 | **WhatsApp (Meta Cloud API)** | P0 — Product promise | Pre-launch | $20–100 |
| 3 | **Email (Amazon SES)** | P0 — Receipts, resets | Pre-launch | $5–20 |
| 4 | **URA EFRIS** | P0 — Legal compliance | Pre-launch | Dev effort only |
| 5 | **Flutterwave / Pesapal** | P1 — Card/B2B | Post-launch | $50–200 |
| 6 | **SMS Gateway** | P1 — Failover/OTP | Post-launch | $10–50 |
| 7 | **Printer/Scanner** | P0 — Hardware pilot | Pre-launch | Hardware only |
| 8 | **Offline Sync** | P1 — Reliability | Post-launch | Dev effort only |

---

## Implementation Notes

1. **Start with MTN MoMo + Airtel Money** — largest revenue enabler, longest provider onboarding.
2. **Parallel**: WhatsApp + Email (shared notification architecture — see `email_whatsappp_notifications.md` §4 target architecture).
3. **URA EFRIS** can run in parallel (different team) — sandbox testing first.
4. **Flutterwave/Pesapal** only after mobile money is live and generating revenue.
5. **SMS** only as WhatsApp failover — not a primary channel.
6. **Offline sync** is architectural — plan early, build late (retrofitting is expensive per `checked.md`).

---

## Provider Account Setup Checklist

| Provider | Account Needed | Docs | Sandbox | Prod Go-Live |
|----------|----------------|------|---------|--------------|
| MTN MoMo | Business registration, MoMo Developer Portal | developer.mtn.com | Yes | Business cert + KYC |
| Airtel Money | Business registration, Airtel Developer Portal | developers.airtel.africa | Yes | Business cert + KYC |
| Meta WhatsApp | Meta Business Manager, WhatsApp Business Account | developers.facebook.com/docs/whatsapp | Yes (test numbers) | Template approval (1–3 days) |
| Amazon SES | AWS Account | docs.aws.amazon.com/ses | Yes (sandbox) | Production access request |
| URA EFRIS | Taxpayer registration, EFRIS cert | URA portal | Yes (test env) | Live cert from URA |
| Flutterwave | Business registration | developer.flutterwave.com | Yes | KYC + settlement account |
| Africa's Talking | Account | developers.africastalking.com | Yes | Auto |

---

> **Note:** All secrets (API keys, tokens, certificates) must be stored in `.env` / deployment secrets, **never committed**. Rotate any exposed credentials immediately (see `checked.md` lines 26–28).