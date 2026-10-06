import { describe, expect, it, vi, afterEach } from 'vitest';
import { configureStore } from '@reduxjs/toolkit';
import { toast } from 'sonner';

import { apiErrorMiddleware } from './apiErrorMiddleware';

/**
 * A request that fails must not be indistinguishable from one that returned nothing.
 *
 * 178 components use a query hook and only 31 read `isError` (checked.md P1-20), so a
 * 500 rendered as "No sales found" and nobody reported it. This middleware is the
 * backstop for the components that say nothing at all.
 *
 * Its honest limitation is asserted rather than hidden: a component that catches and
 * toasts still lets the raw request fail, so this can fire alongside their own message.
 * Doing better needs the rejection surfaced on the action, which RTK Query does not
 * expose — so the trade-off is documented here rather than discovered in production.
 */

/** The shape RTK Query dispatches for a request that failed with a status. */
const rejected = (status: number) => ({
  type: 'probeApi/executeQuery/rejected',
  payload: { status, data: null },
  error: { message: 'Request failed' },
  meta: {
    // All three are required, and guessing any of them makes the action silently never
    // match — which is how the first two versions of this file passed while asserting
    // nothing. isRejectedWithValue() is isRejected() AND a flag: isRejected() wants
    // meta.requestStatus === 'rejected' with meta.requestId a string, and the flag is
    // meta.rejectedWithValue. Setting requestStatus to 'rejectedWithValue' looks right
    // and never matches.
    requestId: `probe-${status}`,
    rejectedWithValue: true,
    requestStatus: 'rejected',
    arg: { type: 'probeApi/failing', originalArgs: undefined },
  },
});

const store = () =>
  configureStore({
    reducer: { probe: (s = 0) => s },
    middleware: (gdm) => gdm().concat(apiErrorMiddleware),
  });

afterEach(() => vi.restoreAllMocks());

describe('apiErrorMiddleware', () => {
  it('reports a 5xx so it cannot be read as empty data', () => {
    const error = vi.spyOn(toast, 'error').mockImplementation(() => '');

    store().dispatch(rejected(503) as never);

    expect(error).toHaveBeenCalledWith(expect.stringContaining('server could not complete'));
  });

  it('does not invent a URL it cannot know', () => {
    // RTK Query puts the endpoint's arguments on the action, not the resolved request
    // path, so a message naming a path would be a guess.
    const error = vi.spyOn(toast, 'error').mockImplementation(() => '');

    store().dispatch(rejected(503) as never);

    expect(error.mock.calls[0][0]).not.toMatch(/https?:\/\//);
  });

  it('gives a plain-English reason for the common statuses', () => {
    const cases: [number, string][] = [
      [404, 'Not found'],
      [422, 'rejected'],
      [429, 'Too many requests'],
      [500, 'server could not complete'],
      [0, 'Check your connection'],
    ];

    for (const [status, expected] of cases) {
      const error = vi.spyOn(toast, 'error').mockImplementation(() => '');
      store().dispatch(rejected(status) as never);
      expect(error.mock.calls[0][0], `status ${status}`).toContain(expected);
      error.mockRestore();
    }
  });

  it('does not talk over a 401 or 403, which session and permission handling own', () => {
    const error = vi.spyOn(toast, 'error').mockImplementation(() => '');

    store().dispatch(rejected(401) as never);
    store().dispatch(rejected(403) as never);

    expect(error).not.toHaveBeenCalled();
  });

  it('ignores ordinary actions', () => {
    const error = vi.spyOn(toast, 'error').mockImplementation(() => '');

    store().dispatch({ type: 'something/else' } as never);

    expect(error).not.toHaveBeenCalled();
  });
});
