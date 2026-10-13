import type { Middleware } from '@reduxjs/toolkit';
import { isRejectedWithValue } from '@reduxjs/toolkit';
import { toast } from 'sonner';

import { describeApiError } from '@/app/utils/errorMessage';

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
 * preferred over the status-based copy; the status copy only fills in for a transport
 * failure where there is no body to read. A 403 is no longer swallowed: a policy refusal
 * now says so instead of rendering nothing.
 */
export const apiErrorMiddleware: Middleware = () => (next) => (action) => {
  if (isRejectedWithValue(action)) {
    const payload = action.payload as { status?: number | string } | undefined;
    const status = payload?.status;

    // A 401 is a dead session, which authListener turns into a sign-out — a toast on top
    // of the redirect is noise. Every other status gets a message.
    if (status !== 401) {
      toast.error(describeApiError(payload, requestFailureMessage(status)).message);
    }
  }

  return next(action);
};

/**
 * No request URL is included: RTK Query does not put the resolved URL on the action,
 * only the endpoint's own arguments, which are usually an id rather than a path. Naming
 * an endpoint that does not match what failed would be worse than saying nothing.
 */
const requestFailureMessage = (status: number | string | undefined): string => {
  if (status === 403) return "You don't have permission to do that.";
  if (status === 404) return `Not found. The record may have been removed.`;
  if (status === 422) return 'The server rejected that request. Check the values and try again.';
  if (status === 429) return 'Too many requests. Wait a moment and try again.';
  if (typeof status === 'number' && status >= 500) {
    return 'The server could not complete that request. Try again in a moment.';
  }

  return 'That request failed. Check your connection and try again.';
};
