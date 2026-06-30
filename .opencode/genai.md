# AI Features — Yet to Be Finished

## 1. Expiring Products Tracking
**Tool:** `ExpiringProducts.php` — exists as a **stub** only.

**Why unfinished:** The `expiry_date` column does not exist on either the `products` or `business_branch_products` table in the database schema.

**What's needed:**
- Add `expiry_date` (date) column to `business_branch_products` (or `products`) via a new migration.
- Update `ExpiringProducts::handle()` to query products where `expiry_date` is within the next N days.
- Optionally add a `notify_before_days` column for pre-expiry alerts.

---

## 2. Agent Intent Classification (LLM Integration)
**File:** `Agent.php`

**Current state:** Simple keyword/fuzzy matching against tool names and descriptions. Falls back to `product_search` on no match.

**What's missing:**
- Integration with an external LLM API (e.g., OpenAI, Anthropic, or a local model via Ollama) for proper intent classification.
- The Agent currently cannot handle complex multi-intent queries or ambiguous phrasing beyond basic keyword matching.
- No conversation memory/context — each message is processed in isolation.

---

## 3. Smart Restocking Prediction
**Not implemented.**

**What it should do:** Predict when a product will run out of stock based on past consumption rate and current quantity, e.g. "You're likely to run out of sugar in 4 days."

**Prerequisites:**
- Historical sales data per product (available in `sale_items` with `quantity` and `created_at`).
- A prediction algorithm (simple linear projection or ML model) in a new tool, e.g. `RestockingPrediction.php`.
- Could leverage existing `ReorderRule` model.

---

## 4. Demand Forecasting
**Not implemented.**

**What it should do:** Identify patterns like "Sales of soda increase every Friday" or seasonal trends.

**Prerequisites:**
- Analyze `sale_items` grouped by `business_branch_product_id` and day-of-week/month.
- New tool e.g. `DemandForecast.php`.
- May need a background job to precompute forecast models.

---

## 5. WhatsApp Notifications (AI-Triggered)
**Not implemented.** The `addons.md` defines a stub architecture (`WhatsAppConfig`, `WhatsAppTemplate`, `WhatsAppMessageLog` models and a `WhatsAppService`), but nothing is built.

**AI-related WhatsApp triggers that could be built:**
- Low stock alert → auto-notify manager via WhatsApp.
- Daily business summary pushed to WhatsApp.
- Unpaid debt reminders.
- Suspicious refund/sale patterns flagged by AI.
- Cash drawer mismatch alerts at shift handover.
- Expiring products alert.

**Requires:** WhatsApp Business API integration, queued notification jobs, and hook points in Agent tools.

---

## 6. Anti-Fraud Audit Trails & Shift Reconciliation
**Listed in `future.md`.** Not implemented.

**What it should do:** When an attendant logs out, the system calculates expected cash/mobile money/stock in drawer. If there is a discrepancy, it alerts the owner via WhatsApp/SMS.

**AI relevance:** Could be an Agent tool like `ShiftReconciliation.php` that analyzes sale_payments, cash_flows, and stock_movements for a given shift/user.

---

## 7. Automated Customer Retention (WhatsApp)
**Listed in `future.md`.** Not implemented.

**What it should do:**
- At checkout, capture customer phone → send digital receipt.
- Log customer buying history.
- Auto-send personalized WhatsApp when a customer's usual product is back in stock.
- Auto loyalty discounts (e.g. "5th visit this month → 5% off").

**AI tools needed:** `CustomerRetention.php`, `PersonalizedRecommendation.php` (or extend existing `CustomerPurchases` tool).

---

## 8. Financial Reporting Exports (PDF/Excel)
**Not implemented for AI use.** The `ReportExport` model exists but no AI tool exposes report generation.

**What's missing:** Tools like `ExportRevenueReport.php`, `ExportProfitLoss.php` that generate downloadable files and return a URL.

---

## 9. Barcode Scanning for AI Product Lookup
**Not implemented.**

**Idea:** An AI tool that accepts a barcode scan and returns the matched product details, stock quantity, and pricing. Could complement the existing `product_search` tool.

---

## 10. Inventory Valuation & Movement Predictions
**Partially implemented.** `StockValuation.php` and `InventoryMovement.php` exist.

**What's missing:**
- Trend analysis — is stock moving faster or slower than last month?
- Dead stock detection — products with zero movement in X days.
- Suggested transfer quantity between branches based on demand.

---

## Architecture Note
The AI subsystem core (Agent, ToolRegistry, Tool base class, AiController, 24 functional tools, frontend AiChat component, RTK Query slice) is **complete and working**. The items above are enhancements that build on this foundation — they were identified in `future.md`, `addons.md`, and `prompt.md` but no implementation has been started.
