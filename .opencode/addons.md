# DuukaFlow — Add-ons & Improvements

> **Format:** Model / Feature -> Why (real-world Ugandan problem) -> How (programmatic implementation)
>
> **Stack:** Laravel API + React SPA
>
> **Note:** WhatsApp features deferred per instructions.

---

## 1. Mobile Money Payment Gateway Integration (MTN MoMo & Airtel Money)

**Why:** Over 70% of Ugandans use mobile money (MTN MoMo, Airtel Money) as their primary payment method. Informal kiosks (`duuka`) and formal retailers lose sales daily because customers carry little cash. Without digital payment acceptance, businesses are locked out of the growing cashless economy. URA also mandates electronic receipts for VAT-registered businesses — mobile money payments automatically generate a digital trail.

**How:**

- Backend: Create a `PaymentGateway` model/table (provider, api_key, api_secret, webhook_secret, business_id, is_active). Implement a `PaymentService` with provider-specific adapters (MTN MoMo SDK, Airtel Money API). Use Laravel's HTTP client for API calls. Webhook endpoints receive payment confirmations and update `sale_payments` status from `pending` to `paid`.
- Frontend: Add "Pay with Mobile Money" button on POS checkout. Show a QR code or USSD prompt. Display payment status polling (every 5s) until confirmed.
- Endpoints: `POST /api/payments/momo/request`, `POST /api/payments/momo/webhook`, `GET /api/payments/{id}/status`.

---

## 2. Offline-First & Sync Engine (PWA + IndexedDB)

**Why:** Internet connectivity in Ugandan trading centres (e.g., Owino Market, Nakasero, upcountry towns) is unreliable and expensive. Shopkeepers cannot afford downtime — a sale missed is money lost. Informal vendors often operate from areas with zero connectivity. An offline-first approach ensures the POS never stops.

**How:**

- Backend: Create a `SyncLog` table (entity_type, entity_id, action, payload, synced_at, business_branch_id). Expose `POST /api/sync/push` (batch upsert of offline records) and `GET /api/sync/pull/{since}` (fetch changes since last sync). Use Laravel's `updated_at` timestamps for conflict detection (last-write-wins or branch-priority).
- Frontend: Convert the React SPA into a PWA (service worker via vite-plugin-pwa, manifest.json). Use Dexie.js (IndexedDB wrapper) to store products, inventory, pending sales offline. On reconnect, push pending transactions via the sync endpoint. Show a sync status indicator (icon turns green/red).
- RTK Query listeners detect online/offline events and trigger sync automatically.

---

## 3. Thermal Receipt Printer & Barcode Scanner Support

**Why:** Ugandan retail runs on paper receipts — customers expect them, and URA requires them for VAT. Handwritten receipts are slow, error-prone, and untaxable. Barcode scanning speeds up checkout from ~2 minutes to 10 seconds. In busy environments (e.g., wholesale shops in Kikuubo), speed is everything.

**How:**

- Backend: Add a `Printer` model (business_branch_id, name, type[bluetooth/usb/network], ip_address, port, is_default). Expose a print receipt endpoint `POST /api/sales/{id}/print` that returns ESC/POS formatted text (thermal printer protocol). Support `ReceiptPrintJob` queued for network printers.
- Frontend: On sale completion, trigger browser print dialog or use `WebUSB`/`WebBluetooth` API for direct thermal printer output. Add a barcode scanner input field that auto-focuses, scans EAN-13/UPC, looks up the product, and adds it to cart. Use the `BarcodeDetector` API (Chromium-based browsers) or a library like `quagga2` for camera-based scanning.
- Generate barcodes as PNG via a library for products missing them.

---

## 4. Supplier Marketplace & Bulk Ordering

**Why:** Ugandan retailers rely on a fragmented supply chain — multiple trips to different wholesalers (e.g., Kikuubo for electronics, Nakawa Market for foodstuffs). Small shops lack bargaining power and pay higher prices. A centralised marketplace connects retailers directly to manufacturers and distributors, with bulk discounts and delivery scheduling.

**How:**

- Backend: Extend `Supplier` to `SupplierListing` (product_id, unit_price, minimum_order_quantity, delivery_time, location, is_verified). Create `BulkOrder` model (business_branch_id, supplier_id, status, delivery_date, total_amount, notes) with `BulkOrderItems`. Add a `SupplierOffer` model for negotiated bulk pricing. Endpoints: `GET /api/marketplace/products`, `POST /api/marketplace/orders`, `GET /api/marketplace/orders/{id}`.
- Frontend: New "Marketplace" tab in admin/manager dashboards. Browse supplier products with price comparison, add to cart, schedule delivery.
- Notify suppliers of new orders via in-app notification (email/WhatsApp later).

---

## 5. Credit & Savings Groups (VSLA/ASCAs Integration)

**Why:** In Uganda, informal Village Savings and Loan Associations (VSLAs) and Accumulating Savings and Credit Associations (ASCAs) are the backbone of community finance — especially for women entrepreneurs. A shopkeeper often doubles as the group's treasurer. DuukaFlow can digitise record-keeping: member contributions, loan disbursement, share purchases, and payout calculations.

**How:**

- Backend: New models — `VslaGroup` (name, business_id, meeting_frequency, share_value, cycle_start, cycle_end), `VslaMember` (group_id, user_id, member_code, share_balance), `VslaMeeting` (date, location, attendance), `VslaContribution` (member_id, meeting_id, type[share/savings/loan_repayment], amount), `VslaLoan` (member_id, amount, interest_rate, duration_weeks, status[active/settled/defaulted], disbursed_at), `VslaPayout` (cycle_end_date, member_id, total_shares, total_savings, interest_earned, payout_amount).
- Endpoints: Full CRUD for groups, members, meetings, contributions, loans, payouts.
- Frontend: New "VSLA" section in admin/manager. Dashboard for group summary, meeting recording screens, loan approval workflow. Print payout slips using the receipt module.
- This feature transforms DuukaFlow from a pure retail POS into a community finance platform.

---

## 6. Multi-Currency & Forex Handling (UGX + USD + KES + TZS)

**Why:** Uganda's economy is dollarised in practice — wholesalers quote in USD, rent is often USD-denominated, and cross-border trade with Kenya/Tanzania is common. A shop selling in UGX but buying in USD needs real-time forex tracking. The Uganda Bureau of Statistics also tracks inflation in both currencies.

**How:**

- Backend: Add `currency` field to `Sale`, `Purchase`, `product` (default UGX). Create a `CurrencyRate` table (base_currency, target_currency, rate, source[bank_of_uganda/black_market], valid_from, valid_to). A scheduled job fetches BoU daily rates via their API. Add a `ForexTransaction` model for currency exchange events.
- In `CashFlow`, add `original_currency` and `original_amount` alongside the UGX-equivalent.
- Frontend: Show price in both UGX and USD on product cards. Add currency toggle on the POS screen. Show exchange rate on the dashboard. Include forex gain/loss reporting in analytics.
- Validation: Prevent transactions in unsupported currencies, warn if rate is stale.

---

## 7. URA Tax Compliance Module (e-Receipting, VAT, LST, WHT)

**Why:** URA requires e-receipts for VAT-registered businesses (turnover > UGX 150M/year). Local Service Tax (LST) and withholding tax (WHT) apply to specific transactions. Manual compliance is error-prone and audits are costly. DuukaFlow can automate tax calculations, generate URA-compliant receipts, and produce tax returns.

**How:**

- Backend: Extend `BusinessTax` to store URA tax codes and rates. Create a `TaxInvoice` model (sale_id, invoice_number, ura_qr_code, generated_at, submitted_to_ura_at). Implement URA EFRIS API integration (register invoice, submit batch, get QR code). Track VAT input/output with `BusinessTaxPayment` linking to purchases (input VAT reclaim).
- Create a `TaxReport` model (business_id, period_start, period_end, total_sales, vat_collected, vat_paid, lst_due, wht_due, status). A scheduled job generates and emails draft returns monthly.
- Frontend: Tax-compliance dashboard showing VAT payable/reclaimable. "Generate URA Invoice" button on each sale. Export tax reports as XLS/PDF for submission.
- This is critical for formal-sector retailers in Kampala, Jinja, Mbale, and other urban centres.

---

## 8. Stock Transfer & Inter-Branch Logistics

**Why:** Businesses with multiple branches (e.g., a hardware store in Kampala + Jinja) move stock between locations. Currently, each branch's inventory is siloed. A manager needs to transfer 50 bags of cement from the main branch to a new branch — this should be a single click, recorded in both inventories, with a paper trail for the driver.

**How:**

- Backend: Create `StockTransfer` model (from_branch_id, to_branch_id, status[draft/in_transit/received/cancelled], transferred_by, received_by, dispatched_at, received_at, notes). `StockTransferItem` (stock_transfer_id, product_id, quantity_expected, quantity_received, status). On approval, decrement source branch stock and create `StockMovement` (out). On receipt, increment destination branch stock (in).
- Endpoints: `GET/POST /api/stock-transfers`, `PATCH /api/stock-transfers/{id}/dispatch`, `PATCH /api/stock-transfers/{id}/receive`, `GET /api/stock-transfers/{id}/manifest` (printable for driver).
- Frontend: Stock Transfer list with status badges. "New Transfer" wizard — select source branch → select products → select destination → confirm. For the receiving branch, a "Pending Receipt" list where staff verifies quantities.
- Add transfer cost tracking (transport, porter fees) to `CashFlow`.

---

## 9. Automated Inventory Reordering & Supplier Alerts

**Why:** A rural shop in Gulu might not notice a product is out of stock until a customer asks. By that time, the supplier runaround adds 2-3 days of lost sales. Low stock thresholds are set but never acted on. DuukaFlow should auto-generate purchase orders and alert suppliers when stock hits reorder level.

**How:**

- Backend: Scheduled job `AutoReorderJob` (daily) scans `product` where `quantity <= reorder_level`. Generates `Purchase` records with status `auto_draft`. Sends in-app notification to manager with "Review & Approve" action. On approval, sends notification to linked suppliers.
- Create a `ReorderRule` model (business_branch_id, product_id, reorder_quantity, preferred_supplier_id, auto_approve[boolean for trusted products]).
- Extend the existing `CheckNotificationsJob` to also check for products that haven't been restocked in X days (dead stock prevention).
- Frontend: "Pending Orders" list with approve/edit/reject actions. "Reorder Rules" settings page.

---

## 10. Employee Shift Scheduling & Biometric Clock-In

**Why:** In Ugandan retail, attendance is often paper-based — sheets get lost, staff sign in for each other ("ghost workers"), and payroll calculations are tedious. Biometric (fingerprint) clock-in eliminates buddy-punching. Shift scheduling ensures coverage during peak hours and prevents wage disputes.

**How:**

- Backend: Extend `AttendanceSettings` with shift definitions (name, start_time, end_time, grace_period_minutes). Create `WorkerShift` model (worker_id, business_branch_id, date, shift_id, status). Create `BiometricDevice` model (business_branch_id, name, device_ip, device_port, device_model, last_sync_at). Implement a simple API `POST /api/attendance/biometric-punch` (worker_code, device_id, timestamp) — devices can POST via HTTP.
- Add a `ShiftSwapRequest` model (requester_id, target_id, date, shift_id, status) for staff to swap shifts.
- Frontend: "Schedule" calendar view in admin/manager. Staff see their weekly schedule. Geofenced check-in fallback (GPS + photo) for branches without biometric devices. Shift swap requests with manager approval.
- Scheduled job to flag missed clock-ins by 10 AM daily.

---

## 11. Loyalty & Customer Rewards Program

**Why:** In Uganda's competitive retail landscape (especially in trading centres), customer retention is everything. Informal shops rely on personal relationships ("I know my customers"), but as shops scale, that relationship breaks. A digital loyalty program — points, stamps, referrals — keeps customers coming back. MVPs (Most Valuable Patrons) can be identified and rewarded.

**How:**

- Backend: Create `LoyaltyProgram` model (business_id, type[points/stamps/tiered], points_per_currency, redemption_rate, expiry_days). `LoyaltyCard` (business_id, customer_id, points_balance, tier[ bronze/silver/gold/platinum ], issued_at, last_used_at). `LoyaltyTransaction` (card_id, sale_id, type[earn/burn/adjust], points, reference). `LoyaltyReward` (business_id, name, points_required, description, image_url, stock).
- Endpoints: `POST /api/loyalty/earn` (attach to sale completion), `POST /api/loyalry/redeem` (burn points), `GET /api/loyalty/balance/{customer}`.
- Frontend: Customer profile shows points balance and tier. Checkout shows "Earn X points on this purchase". Redemption catalog visible on POS. Admin controls program settings.
- Use the existing `PromotionsSettings` as a foundation.

---

## 12. Data Export & Analytics Dashboard (Power BI / Metabase Bridge)

**Why:** Shop owners need to see trends — which products sold best last month, which branch is underperforming, seasonal patterns. The current in-app analytics is basic. Exporting to Excel/Power BI allows deeper analysis, investor reporting, and loan applications (banks ask for financial statements).

**How:**

- Backend: Create a `ReportExport` model (business_id, report_type, format[csv/xlsx/pdf], parameters, file_path, generated_at, expires_at). Jobs generate exports asynchronously and store on S3/local disk. Add `GET /api/reports/export` with params (type, date_from, date_to, branch_id, format). Use Laravel Excel (Maatwebsite) for XLSX/CSV generation. Use Barracuda/Dompdf for PDF.
- Create a read-only PostgreSQL user per business for direct BI tool connection (Power BI, Metabase, Superset). Document the schema for external querying.
- Add a "Schedule Report" feature — weekly/monthly email delivery.
- Frontend: "Export" button on every report page with format picker and date range. "Scheduled Reports" settings page. Download center with generated files.

---

## 13. 2FA & Role-Based Access Hardening

**Why:** In shared retail environments, staff share devices — a cashier leaves themselves logged in, and a colleague processes a fake refund. PIN codes, session timeouts, and two-factor authentication for admin actions (bulk discounts, supplier payments) prevent internal fraud. URA also requires strong access controls for e-invoicing.

**How:**

- Backend: Add `2fa_secret` and `2fa_enabled` to `User`. Use `laravel/fortify` or implement TOTP manually (google2fa library). Add `sudo_mode` (re-authenticate for sensitive actions like deleting sales, editing prices, paying suppliers). Create `LoginHistory` model (user_id, ip, user_agent, success, failed_at).
- Pin-code quick login for staff POS (4-digit PIN stored as bcrypt hash on the device with biometric fallback). Session timeout after 5 minutes of inactivity for POS.
- Frontend: After login, prompt to enable 2FA (QR code for Google Authenticator). "Sudo mode" modal that expires after 15 minutes. POS pin screen after idle timeout.
- Rate-limit login attempts (5 failures = 15-minute lockout) with `throttle` middleware.

---

## 14. WhatsApp Integration (Future — Stub Architecture)

Although deferred, the architecture should be ready:

- Create a `WhatsAppConfig` model (business_id, phone_number_id, access_token, webhook_verify_token, is_active).
- Create a `WhatsAppTemplate` model (name, body, variables, status[approved/pending/rejected]) for URA-approved message templates.
- Create a `WhatsAppMessageLog` model (business_id, recipient, template, variables, status[sent/delivered/read/failed], sent_at, delivered_at, read_at).
- Service: `WhatsAppService` with methods `sendTemplate()`, `sendText()`, `sendMedia()`. Use the Cloud API (graph.facebook.com) or a local gateway (e.g., WATI, 360dialog).
- Queue all sends via `WhatsAppNotificationJob` with retries (3 attempts, exponential backoff).
- Hook points: low stock alerts, sale receipts, daily summary, payment reminders, birthday greetings.

---

## 15. Best Practices & Code Quality Improvements

### Backend (Laravel)

- **API Versioning:** Prefix all routes with `v1` (`/api/v1/...`). Use route groups for version negotiation via `Accept` header.
- **API Resources:** Replace raw `return response()->json(...)` with Laravel API Resource classes (`SaleResource`, `ProductResource`) for consistent envelopes (`{ data, meta, links }`).
- **Form Requests:** Ensure every store/update endpoint has a dedicated Form Request with `rules()` and `authorize()`.
- **Response Macros:** Register `ApiResponse` macros in `AppServiceProvider` — `success()`, `error()`, `notFound()`, `validationError()`.
- **Repository/Service Layer:** Enforce the existing service layer pattern. Services should handle business logic; controllers only orchestrate.
- **Model Relationships:** Fix `BusinessBranch::sales()` (currently `belongsTo` — should be `hasMany`), and `BusinessBranch::business()` (missing `return`).
- **Stock Movements:** Ensure stock movements reference `business_branch_product_id` consistently, not `product_id`.
- **Centralised Exception Handling:** Customise `Handler.php` to return JSON errors consistently. Map exceptions to HTTP status codes.
- **Rate Limiting:** Apply `throttle:60,1` to all authenticated routes, `throttle:5,1` to login.
- **Testing:** Aim for feature tests covering all CRUD endpoints. Use factories + faker. Minimum: test sale creation adjusts stock, purchase creation increments stock, auth middleware works.
- **Scheduler:** Document all scheduled jobs in `routes/console.php` with `->withoutOverlapping()` and `->runInBackground()`.
- **Environment:** Move all secrets to `.env`, validate in `config/services.php`. Never hardcode credentials.

### Frontend (React)

- **TypeScript strict mode:** Enable `strict: true` in `tsconfig.json`. Replace all `any` types with proper interfaces generated from OpenAPI/Swagger docs.
- **API response types:** Define `ApiResponse<T>`, `PaginatedResponse<T>` generics. Consistently access `data` property.
- **Error boundaries:** Wrap each route section in an `ErrorBoundary` component with fallback UI.
- **Loading skeletons:** Every list/card should show a skeleton placeholder (`<Skeleton>`) while data loads.
- **Remove `console.log`:** Add an ESLint rule (`no-console: warn`) to catch leftover debug logs.
- **RTK Query polling:** Use `pollingInterval` on real-time data (notifications, pending sales) rather than manual refetch.
- **Mobile-first:** Test all screens at 360px width. Use responsive grid (`grid-cols-1 md:grid-cols-2 lg:grid-cols-3`). Touch-friendly buttons (min 44px height).
- **PWA manifest:** Complete the `vite-plugin-pwa` config with icons, splash screens, and theme color.
- **Environment variables:** Create `.env.example` for the UI. Document all `VITE_*` variables.
- **Performance:** Lazy-load route components (`React.lazy` + `Suspense`). Memoize expensive calculations (`useMemo`, `React.memo`). Virtualize long lists (`react-window`).

### DevOps & Infra

- **CI/CD:** Add GitHub Actions workflow — `lint`, `phpstan`, `pint`, `pest`, `vite build` on push/PR. Deploy on tag.
- **Docker for frontend:** Add a `Dockerfile` and `compose.override.yaml` for the UI so devs don't need Node installed locally.
- **Health endpoint:** `GET /api/health` returns `{ status: "ok", version, db_connected, queue_connected, last_sync }`.
- **Logging:** Centralised structured logging (Laravel to stderr, use Papertrail/Logtail or ELK). Add request IDs to every log line.
- **API documentation:** Install `scramble` or `scribe` to auto-generate OpenAPI docs from route annotations. Serve at `/api/docs`.
- **Seeding:** Create `Database\Seeders\DemoDataSeeder` that creates a complete demo business with products, sales, customers — usable for onboarding and dev.

---

## 16. Frontend UX Gaps to Address

- **Home page `/`**: Currently a generic landing. Should be tailored to DuukaFlow value props: "Inventory that understands Uganda."
- **Admin dashboard**: Currently only shows workers count. Add widgets: today's sales, low stock count, pending orders, recent activity feed.
- **POS checkout flow**: Should be keyboard-driven (enter SKU, F1 = pay cash, F2 = mobile money, F3 = credit). Split-screen: left = cart, right = product grid.
- **Notification centre**: Add a bell icon in the global header (always visible). Dropdown shows last 5 unread. Click "View all" goes to notifications page.
- **Empty states**: Every list page should have a thoughtful empty state (illustration + "No sales yet" + action button).
- **Search**: Add global search (Ctrl+K) that searches products, customers, sales, suppliers. Use a combobox/dialog pattern.
- **Bulk actions**: Allow selecting multiple sales/products/customers and performing bulk actions (delete, status change, export).
- **Date range picker**: Replace hardcoded `last_7_days` with a configurable date range on all analytics and reports pages.

---

## 17. Priority Matrix

| Priority | Feature               | Effort    | Impact                  |
| -------- | --------------------- | --------- | ----------------------- |
| P0       | Mobile Money Payments | High      | Critical — core revenue |
| P0       | URA Tax Compliance    | High      | Legal requirement       |
| P1       | Offline-First Sync    | Very High | Reliability             |
| P1       | Stock Transfer        | Medium    | Multi-branch ops        |
| P2       | Auto Reordering       | Medium    | Inventory efficiency    |
| P2       | Loyalty Program       | Medium    | Customer retention      |
| P2       | Thermal Printing      | Low       | Daily ops speed         |
| P2       | 2FA & Security        | Medium    | Fraud prevention        |
| P3       | Supplier Marketplace  | High      | Expansion feature       |
| P3       | VSLA Integration      | High      | Community finance       |
| P3       | Multi-Currency        | Medium    | Cross-border trade      |
| P3       | Shift Scheduling      | Medium    | Workforce mgmt          |
| P3       | Data Export           | Low       | Reporting               |
| P3       | Best Practices        | Ongoing   | Code quality            |

---

## 18. Fixed Bugs (from audit)

These bugs should be fixed immediately before building new features:

1. **Missing `finances` API endpoint** — frontend calls `/api/finances/` but no route exists (404). Create `FinanceController` and routes.
2. **`auth.php` routes broken** — references `PurchaseController@login`. Either remove the file or implement correctly in `UserController`.
3. **`orders.php` routes broken** — same as above. Either implement or remove.
4. **`BusinessBranch::sales()`** — uses `belongsTo(Sale::class)` instead of `hasMany(Sale::class)`.
5. **`BusinessBranch::business()`** — missing `return` statement.
6. **`StockMovement.product_id`** — unclear if this references `Product` or `product`. Add proper foreign key.
7. **Security tokens in `.env`** — ensure `.env` is in `.gitignore`. Rotate any committed keys.
