# Front end diagnosis

On the front end, especially on dashboards, when the user tries to move from a page to another, he's welcomed by a toast error,
_That request failed. Check your connection and try again._

## Task

Try to find out the reason why, and look for a possible solution to the problem, then note the solution down here

## Solution

### Root cause

The toast text `"That request failed. Check your connection and try again."` is the generic fallback in `requestFailureMessage()` (`ui/src/app/store/apiErrorMiddleware.ts:60`). The middleware fires `toast.error()` for **every** `isRejectedWithValue` action whose payload has no human-readable `data.message` — which covers:

1. **Transport-level failures** — `fetchBaseQuery` wraps network errors, CORS failures, and aborted requests as `{ status: 'FETCH_ERROR' }` (or `PARSING_ERROR` / `TIMEOUT_ERROR`). These have no `data.message`, so the middleware shows the generic toast.
2. **Unmapped HTTP statuses** — statuses outside the explicit 403/404/422/429/5xx branches (e.g. 400, 409, 419) also fall through to the generic copy.

On dashboards, navigating between pages causes React Router to unmount the old page and mount the new one. The new page's query hooks fire immediately. If any of those requests hits a transient network hiccup, a proxy/CORS issue, or a backend endpoint that returns an unmapped status, the rejection bubbles to the middleware and the toast appears — even though the page itself loads fine from cache or the error is inconsequential.

### Why it feels navigation-specific

- The middleware is a **backstop** (per its own doc comment): it fires for every rejection that no component explicitly handles via `isError`.
- Dashboard pages fire queries on mount. A navigation always triggers a fresh set of queries on the destination page. If any one of them fails at the transport level or returns an unmapped status, the toast fires.
- The user perceives this as "navigation causes the error toast" because that's when they see it — the page content often loads from cache or the error is invisible beyond the toast.

### Planned fix

1. **Suppress transport-level rejections in the middleware** — skip the toast when `payload.status` is `'FETCH_ERROR'`, `'PARSING_ERROR'`, or `'TIMEOUT_ERROR'`. These are not actionable by the user and are almost always transient (aborted polls, proxy hiccups). A genuine network outage already shows its own signals (page stays on the loading state, retry UI appears).

2. **Narrow the middleware's scope further** — only toast for HTTP status codes (numeric `payload.status`), not string transport codes. This is a one-line guard: `if (typeof status !== 'number') return;`.

3. **Add the missing status codes** — map `400` to a plain-English message so it stops falling through to the generic copy.

4. **Test** — extend `apiErrorMiddleware.test.ts` with cases for `'FETCH_ERROR'`, `'PARSING_ERROR'`, `'TIMEOUT_ERROR'`, and `400` to lock in the behaviour.

### Files to change

- `ui/src/app/store/apiErrorMiddleware.ts` — add transport-status guard, map 400.
- `ui/src/app/store/apiErrorMiddleware.test.ts` — add test cases.
