# DuukaFlow — Plan: A2. Barcode scanning at POS (roadmap P1)

> Scope for the next feature after A1 (`refactor.md` A2). Ordered: physical setup first, then
> backend, then frontend, then tests. This replaces the placeholder `plan.md`.

## 1. How scanning actually works without a scanner gadget

There is **no dedicated barcode scanner hardware**. The two realistic gadgets:

| Option | How it works | USB needed? | Effort |
|---|---|---|---|
| A. USB barcode scanner (HID/keyboard-wedge) | Plugs into the PC's USB port, appears as a keyboard. On scan it *types* the barcode digits then presses `Enter`. Zero software. | Yes | Buy ~$15 device |
| B. Phone as USB scanner | Phone camera scans the barcode; phone *itself* acts as a USB-HID keyboard to the PC and types the digits + `Enter`. | Yes (USB cable) | Install app, one-time setup |

Your stated setup = **Option B (phone via USB)**. This is the exact same input model as Option A,
because the PC receives plain keystrokes ending in `Enter`. That is the classic **keyboard-wedge**
pattern — so the app needs **no USB/WebUSB/WebHID APIs at all**. It only needs to react to:
`<focused input gets 8–20 digits quickly>` then `<Enter>`.

Steps to make the phone act as a USB keyboard-scanner (documented for you, not code):
1. Connect phone to PC via a USB data cable.
2. Install a keyboard-wedge scanner app (e.g. an "External Keyboard Helper"-style app, or a
   camera-barcode app with a USB-HID mode) that puts the phone in USB gadget / HID profile so the
   PC sees a keyboard.
3. In POS, click the scan field once so it is focused.
4. Scan a product with the phone camera → digits appear in the scan field → `Enter` is sent automatically.
5. The product is added to the cart.

If the phone can't do HID-over-USB reliably, a cheap USB scanner (Option A) uses the *same* code path.

## 2. Current state (verified)

- `products.barcode` nullable string column already exists; seeded products have barcodes (`8901000000xx`).
- `PosService::searchProducts()` already matches barcode with a prefix `ILIKE` and ranks barcode first.
- `GET /pos/products/search?q=` exists; POS frontend has one search input with
  `onKeyDown={handleBarcodeSubmit}`: on `Enter`, if search returns **exactly one** product it adds it
  to the cart and clears the field. Otherwise it just shows the picker.

What's missing:
- **Exact, deterministic barcode lookup** — the current flow uses fuzzy search and depends on "exactly
  one result", which silently fails on duplicates/prefix matches.
- **A dedicated scan fast-path** that always re-focuses the field and gives scanner-style feedback
  (found → added, not-found → flash/error) independent of the debounced fuzzy search.
- **Tests** for the exact-match lookup + branch scoping.

## 3. Backend changes (small — 1 route, reuse existing service)

1. **New route** `GET /pos/products/by-barcode/{barcode}` in `api/routes/pos.php`
   (inside the existing `auth:sanctum` group).
   - Controller method `PosController::byBarcode($barcode)`:
     - `Product::where('barcode', $barcode)->first()` — auto tenant/branch-scoped via `BaseModel`
       global scopes (`business_id` + `EffectiveBranchScope`), so a user can **only** resolve products
       in their branch(es).
     - Found → `PosProductResource` (keeps tax/stock/markup fields identical to search results, so the
       cart path is unchanged). Not found → `404 {"message": "Product not found"}`.
   - Rules: `barcode` required, `max:100` (a `FormRequest` mirroring `PosProductSearchRequest`, or a
     `where`/regex coercer that strips whitespace/newlines a keyboard-wedge scanner may append).
2. **Optionally add a `scanByBarcode` method to `PosService`** to keep the exact-match query next to
   `searchProducts` (consistency). Exact match first; fall back to `searchProducts` is a frontend
   decision, not backend.

**No model/migration changes.** This is the whole backend.

## 4. Frontend changes (`ui/src/app/pages/dashboards/shared/pos/PosPage.tsx` + `posQuery.ts`)

1. `posQuery.ts`: add `searchProductByBarcode: builder.query<any, string>` → `GET /products/by-barcode/{code}`.
2. `PosPage.tsx` — add a **scan fast-path** without removing the existing search picker:
   - Keep the current search input as the scan field (already autofocused on mount).
   - Refactor `handleBarcodeSubmit` (Enter key): stop using the debounced `triggerSearch`. Instead:
     1. Trim the query; strip trailing newline/tab the scanner may inject.
     2. Call `byBarcode(query)` directly.
     3. Exact hit → `addToCart(product)`, clear field, keep focus, brief success flash.
     4. 404/miss → fall back to `triggerSearch(query)`; if a single fuzzy result exists, add it
        (preserves working barcode-prefix behaviour); otherwise show "No product found" toast + red
        flash on the input, clear field, refocus.
   - Guard against the scanned-string being a partial keystroke mid-burst:
     `byBarcode` runs on `Enter` only, and keyboard-wedge always finishes a full code before `Enter`,
     so this is safe. (Debounced `handleSearch` still updates the picker in the background.)
   - Re-focus the scan field after every addToCart/action (some current paths blur it).
   - Optional affordance: keep placeholder "Scan barcode, SKU, or name…" and add a small scan icon key
     hint (`⌘/Ctrl`-free; keyboard-wedge only needs the field focused).
3. Scanner feedback (CSS-only, no libs): e.g. `ring` turns green on match / red on miss; a 300ms flash.
   Optional beep via a small `AudioContext()` beep — skip if noisy; default **off**.

## 5. Tests

- `PosSearchTest` or new `PosBarcodeTest` (`api/tests/Feature/POS/`):
  1. `by-barcode` resolves an exact barcode → 200, `PosProductResource` shape.
  2. `by-barcode` missing → 404.
  3. **Scoping**: product in another branch → not resolvable (404/empty) for a branch-scoped user.
  4. whitespace/trailing-newline in the code → still resolves (strip before query).
- Frontend: no unit-test infra for pages (manual), so keep the fast-path logic as a small
  pure helper if easy; otherwise manual QA listed in §7.

## 6. Execution order

1. Write/confirm `plan.md` with you (this doc).
2. Backend: service method + controller method + route + request coercion. `php -l` + tests.
3. Frontend: `posQuery.ts` endpoint → `PosPage.tsx` scan fast-path + feedback → `tsc` + eslint.
4. Run full suite via `docker exec duukaflow-backend-1 php artisan test` (baseline 103 passed / 2 failed
   POS-auth tests unrelated to this work).
5. Mark `refactor.md` A2 + `roadmap.md` P1 row done.

## 7. Manual QA (you, with the phone)

1. Seed DB (`migrate:fresh --seed`) so products have barcodes.
2. Open POS, focus scan field.
3. Phone (USB HID mode) scans e.g. `890100000001` → iPhone 15 adds to cart, field clears & refocuses.
4. Scan a garbage code → red flash + "No product found", field clears, POS doesn't break.
5. Type a name/SKU normally → picker still works as before.

---

Questions to you before building:
- Is your phone **Android** (HID-over-USB is easy) — or iPhone (AirPrint/camera keyboard apps only,
  HID-over-USB not standard)? This changes the §1 setup doc wording, not the code.