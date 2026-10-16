# Account Verification — Plan

Companion to `launch-list.md` §2 (Email Delivery). Covers what happens **after a
self-serve signup finishes onboarding**: the owner is sent a verification email and
landed on a "check your inbox" screen. Keeps the existing business-level token model.

---

## 1. Where we are today

**Already built (reuse, do not rewrite):**

| Piece | Location |
|---|---|
| Columns `email_verified_at`, `email_verification_token`, `status` | `app/Models/Business.php:17`, migration `2026_01_01_000002_create_businesses_table.php:32` |
| Mailable | `app/Mail/BusinessVerification.php` |
| Template | `resources/views/mail/business/verification.blade.php` |
| Send / verify / resend logic | `app/Services/Mail/BusinessVerificationMailService.php` |
| Queued send after commit | `app/Listeners/SendBusinessVerificationEmailListener.php` (wired in `WhatsAppEventServiceProvider.php:24`) |
| Verify + resend endpoints | `app/Http/Controllers/EmailVerificationController.php`, `routes/api.php:69-73` |
| Event fired on business create | `BusinessService.php:55-59` (`status = pending_verification`, `BusinessRegistered`) |

**Gaps that make the flow a dead end today:**

1. **No frontend `/verify-email` route.** The emailed link points at
   `{frontend_url}/verify-email?token=…`, but the SPA has no such route
   (`tocheck.md:8`). Every verification link 404s.
2. **Redirect target mismatch.** `EmailVerificationController::verify()` redirects
   browsers to `/onboarding?verified=1` (`:45`) and errors to `/verify-email?error=…`
   (`:83`). Neither is a real page.
3. **No "email sent" screen.** Onboarding hard-navigates to `/dashboard` and toasts
   "Welcome…" (`Onboarding.tsx:175-180`). The owner never learns to check their inbox.
4. **No token expiry** even though the email promises 24 hours (`verification.blade.php:30`).
   Tokens are plaintext, non-expiring, and reusable until consumed.
5. **Nothing is gated on `email_verified_at`.** `RequireBusiness` only checks
   `business_id`. An unverified business has full access.
6. **Resend is unthrottled and unused.** The endpoint exists but no UI calls it, and it
   has no cooldown.
7. **`me` doesn't expose verification state**, so the client cannot show a banner.

---

## 2. Decisions (locked)

- **Scope:** verification stays on **Business** (`Business.email_verified_at`), matching
  `today.md` and existing code. The email goes to `business.email ?? user.email`.
- **Gating:** **soft gate.** Dashboard stays usable; a persistent banner + reminder
  nudges verification. Never lock a working tenant out over email deliverability.
- **Route:** **one `/verify-email` route.** `?token=` present → process token; absent →
  "we sent an email to X" check-inbox state.

---

## 3. Target flow

```
Onboarding steps 1-4
   └─ POST /api/dashboard/business
        ├─ business created  status=pending_verification, email_verified_at=null
        ├─ BusinessRegistered event (in-transaction)
        └─ SendBusinessVerificationEmailListener (after commit)
             └─ BusinessVerificationMailService::send()  → email with /verify-email?token=…

   response 201 { business, email: "...", email_verified: false }
   └─ UI navigates to  /verify-email            (check-inbox state)

Owner opens email
   └─ GET /verify-email?token=…                 (SPA page)
        └─ GET /api/verify-email?token=…
             ├─ 200 → business active, email_verified_at=now, token nulled
             │        page shows "Email verified" → Continue to dashboard
             └─ 404 → "Invalid or expired link" → Resend button

Dashboard (any role)
   └─ if business.email_verified_at == null → soft VerifyEmailBanner
```

---

## 4. Phase 1 — Backend hardening

### 4.1 Token TTL + hashing
- New migration `add_email_verification_expiry_to_businesses_table`:
  `email_verification_expires_at` (nullable timestamp), `email_verification_sent_at`
  (nullable timestamp).
- `BusinessVerificationMailService::generateToken()`:
  - emit `Str::random(64)` (plaintext, goes in the URL),
  - store `hash('sha256', $token)` in `email_verification_token`,
  - set `email_verification_expires_at = now()->addHours(24)`,
    `email_verification_sent_at = now()`.
- `verify()`: look up by token **hash**, reject when
  `expires_at` is null or in the past; on success null both `token` and `expires_at`
  and set `status = 'active'`.
- Single-use already implied by nulling; expiry makes it enforceable.

### 4.2 Align the link and redirects
- `BusinessVerificationMailService` base URL stays `{frontend_url}/verify-email`.
- `EmailVerificationController::verify()` browser redirect → `/verify-email?verified=1`
  (was `/onboarding?verified=1`). Error redirect already targets `/verify-email?error=`.
- Keep the JSON branch for API/SPA callers unchanged.

### 4.3 Resend safety
- Add `throttle` middleware to `POST /api/verify-email/resend` (named limiter, e.g.
  6/hour per user) plus a server-side cooldown: refuse when
  `email_verification_sent_at` is under 60s old, returning 200 with a generic message
  (do not leak throttle state).
- Already-verified → 200 "already verified" (idempotent, no email sent).

### 4.4 Expose state to the client
- `UserController::me()` (`:59-82`): add `email_verified` (bool) and `business_status`
  alongside the existing `onboarding` block, e.g.
  `'email_verified' => (bool) $user->business?->isEmailVerified()`. Null business → false.
- `BusinessController::store()` response: add `email_verified => false` and the resolved
  address the mail was sent to, so the check-inbox page can name it without a second call.

### 4.5 Notification catalogue note
The verification email **stays a direct Mailable**, not a `notification_deliveries`
entry: it carries a one-time token and must be re-sendable, which the catalogue's
deterministic `dedupe` key would block. `registration.welcome` (email + WhatsApp,
`config/notifications.php:59`) remains the **post-verification** welcome, fired once
`email_verified_at` is set. Two messages, two jobs: verify first, welcome after.

---

## 5. Phase 2 — Frontend

### 5.1 New page `ui/src/app/pages/public/VerifyEmail.tsx`
Single component, two states driven by the URL:

- **`?token=` present** → `verifying` → call `GET /api/verify-email?token=`:
  - success → "Email verified", CTA **Continue to dashboard** (or login if no session).
  - failure → "This link is invalid or has expired", Resend + "Back to login".
- **no token** → **check-inbox** state:
  - "We sent a verification email to **{email}**."
  - **Resend email** button → `POST /api/verify-email/resend` (disabled with a 60s
    cooldown after success).
  - "Wrong address? Update it in Settings." hint.
  - Secondary **Continue to dashboard** link (soft gate).
- **`?error=…`** (controller fallback redirect) → show the error message.
- Email source precedence: router `state`, then `sessionStorage`, then `me`.

### 5.2 Wire the router / public list
- `ui/src/app/routes/AppRoutes.tsx`: add `<Route path='verify-email' element={bounded(<VerifyEmail />)} />`.
- `ui/src/lib/routes.ts`: add `/verify-email` to `PUBLIC_PATHS` (token-driven, needs no
  session; a not-yet-verified owner may be signed out on another device).
- Extend `publicRoutes.test.tsx` / `routes.test.ts` to assert the two lists still agree.

### 5.3 Onboarding handoff
`Onboarding.tsx:174-180` — replace `window.location.href = '/dashboard'` with a
navigation to `/verify-email` carrying the address, and change the toast to
"We sent a verification email to {email}." Do **not** clear the session; the owner is
signed in and the check-inbox page needs `me` for the banner path.

### 5.4 Resend mutation
Add `resendVerification` to `onboardingQuery.ts` (or a small `verificationQuery`) using
`providesTags`/`invalidatesTags` so the banner and page reflect success.

---

## 6. Phase 3 — Soft gate + banner

- New `ui/src/app/components/VerifyEmailBanner.tsx`: reads `me.email_verified`, renders a
  dismissible-but-persistent amber strip ("Verify your email to secure your account")
  with a Resend action and a link to `/verify-email`.
- Mount it in the shared dashboard layout(s) — `ExecutiveLayout.tsx` first, then
  BranchManager/Operations/Procurement — so it appears on every dashboard page.
- **No** backend hard gate now. Reserve `RequireVerifiedEmail` middleware for the future
  privileged set (subscription checkout, go-live) if we later decide actions — not the
  whole app — need verification.

---

## 7. Phase 4 — Tests

**Backend (`api/tests/Feature`):**
- verify with valid token → 200, `status=active`, token + expiry nulled.
- verify with expired token → 404.
- verify with unknown/garbage token → 404.
- verify twice → second call does not re-activate or leak state.
- resend: unauthenticated → 401; verified → 200 no-op; within cooldown → no second send;
  throttle over the limit → 429.
- business creation response includes `email_verified: false`.
- `me` returns `email_verified` true/false/null-business.

**Frontend (`ui/src`):**
- `/verify-email` renders check-inbox with no token, success with a valid token
  (MSW), and error with a bad token.
- resend button calls the mutation and enforces the cooldown.
- onboarding success navigates to `/verify-email`, not `/dashboard`.
- `routes.test.ts` public-list agreement.

---

## 8. Copy (first pass)

**Email subject:** `Verify your email to activate your AfroDuuka account`
(**body:** existing `verification.blade.php`; confirm the 24-hour line matches the real TTL).

**Check-inbox page:**
> **Check your inbox**
> We sent a verification link to **{email}**. Click it to activate your business and
> secure your account. It expires in 24 hours.
> [ Resend email ] [ Continue to dashboard ]

**Banner:**
> Your email isn't verified yet. [Resend] · [Verify now]

---

## 9. Security checklist

- Token hashed at rest (sha256); plaintext only ever in the URL.
- Hard 24h expiry; single-use (nulled on success).
- Resend rate-limited per user + 60s cooldown; responses do not reveal throttle state.
- Verify endpoint constant-time-ish: lookup by hash, same 404 for unknown and expired.
- Link travels over HTTPS only; no token in server logs (`EmailVerificationController`
  must not log the query string).
- `.env` secrets for Resend/SES never committed (see `launch-list.md` closing note).

---

## 10. Open items

- **Reminder cadence:** send a follow-up verification nudge at 24h/72h via the
  notification catalogue (`system`, mandatory), or keep it single-mail for MVP?
- **Domain reputation:** confirm Resend domain verification before launch so links land
  outside the free-tier test address (`tocheck.md:9`).
- **Password reset parity:** `password resets` are named in `launch-list.md` §2 but rely
  on Laravel's default user-level reset — decide whether reset follows this same
  `/verify-email` style page or Laravel's own routes.
