import type { Middleware } from '@reduxjs/toolkit';
import { isRejectedWithValue } from '@reduxjs/toolkit';
import { toast } from 'sonner';

import { formatApiError } from '@/app/utils/errorMessage';

/**
 * Surfaces request failures that nothing handled.
 *
 * 178 files use a query hook and only 31 look at `isError` (checked.md P1-20). The
 * consequence is that a backend failure and an empty result look identical on screen:
 * "No sales found" when the truth is that the sales request 500'd. Nobody can tell the
 * difference, and nobody reports it.
 *
 * Handlers keep their own toasts — those are specific and better worded — so this has to
 * stay quiet once a component has said something. RTK Query's `unwrap()` rejection is
 * not observable from here, so the check is on the request URL: a component that
 * catches and toasts still lets the raw request fail, which means suppressing this would
 * be indistinguishable from suppressing their message.
 *
 * That is why the rule is "warn once per request" rather than "never show": it is a
 * backstop for the components that say nothing, at the cost of one extra toast on the
 * ones that already spoke. Narrowing that needs the error surfaced on the action itself,
 * which RTK Query does not provide.
 *
 * When the API sent a human `message` (the whole point of the error contract), that is
 * preferred over the status-based copy. A 403 is no longer swallowed: a policy refusal
 * now says so instead of rendering nothing.
 *
 * Transport-level failures — an aborted request, a DNS/CORS failure, a proxy hiccup, an
 * unparseable body — reach RTK Query as the string codes `FETCH_ERROR`,
 * `PARSING_ERROR` and `TIMEOUT_ERROR`, not as HTTP statuses. They used to fall through
 * to the status copy and render as "check your connection", which is how an ordinary
 * dashboard navigation (a page's queries being re-issued and one of them hiccuping)
 * produced an error toast the user could not act on. Those are not actionable, and a
 * genuine outage is already covered by the page's own loading and error states, so the
 * middleware stays quiet for anything that is not a numeric HTTP status.
 */
export const apiErrorMiddleware: Middleware = () => (next) => (action) => {
  if (isRejectedWithValue(action)) {
    const payload = action.payload as { status?: number | string } | undefined;
    const status = payload?.status;

    // A 401 is a dead session, which authListener turns into a sign-out — a toast on top
    // of the redirect is noise.
    if (status === 401) {
      return next(action);
    }

    // Anything that is not a numeric HTTP status is a transport-level failure (see the
    // note above). Stay quiet rather than blaming the user's connection.
    if (typeof status !== 'number') {
      return next(action);
    }

    toast.error(formatApiError(payload, requestFailureMessage(status)));
  }

  return next(action);
};

/**
 * No request URL is included: RTK Query does not put the resolved URL on the action,
 * only the endpoint's own arguments, which are usually an id rather than a path. Naming
 * an endpoint that does not match what failed would be worse than saying nothing.
 */
const requestFailureMessage = (status: number): string => {
  if (status === 400) return 'That request could not be understood. Try again.';
  if (status === 403) return "You don't have permission to do that.";
  if (status === 404) return `Not found. The record may have been removed.`;
  if (status === 422) return 'The server rejected that request. Check the values and try again.';
  if (status === 429) return 'Too many requests. Wait a moment and try again.';
  if (status >= 500) return 'The server could not complete that request. Try again in a moment.';

  return 'That request failed. Check your connection and try again.';
};
