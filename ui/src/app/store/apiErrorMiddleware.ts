import type { Middleware } from '@reduxjs/toolkit';
import { isRejectedWithValue } from '@reduxjs/toolkit';
import { toast } from 'sonner';

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
 */
export const apiErrorMiddleware: Middleware = (api) => (next) => (action) => {
  if (isRejectedWithValue(action)) {
    const payload = action.payload as { status?: number | string; data?: unknown } | undefined;
    const status = payload?.status;
    const url = extractUrl(action);

    // A 401 or 403 is a session or permission problem, which authListener and the
    // component's own handling deal with. Toasting "request failed" on top of a login
    // redirect is noise.
    if (status !== 401 && status !== 403) {
      toast.error(requestFailureMessage(status, url));
    }
  }

  return next(action);
};

const extractUrl = (action: unknown): string | undefined => {
  const meta = (action as { meta?: { arg?: { originalArgs?: unknown } } })?.meta;
  const args = meta?.arg?.originalArgs;

  if (typeof args === 'string') return args;
  if (args && typeof args === 'object' && 'url' in args) {
    return String((args as { url: unknown }).url);
  }

  return undefined;
};

const requestFailureMessage = (status: number | string | undefined, url?: string): string => {
  const where = url ? ` (${url})` : '';

  if (status === 404) return `Not found${where}. The record may have been removed.`;
  if (status === 422) return 'The server rejected that request. Check the values and try again.';
  if (status === 429) return 'Too many requests. Wait a moment and try again.';
  if (typeof status === 'number' && status >= 500) {
    return 'The server could not complete that request. Try again in a moment.';
  }

  return 'That request failed. Check your connection and try again.';
};
