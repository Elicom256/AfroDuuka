import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { BaseQueryFn, FetchArgs, FetchBaseQueryError } from '@reduxjs/toolkit/query';
import { sharedApiTagTypes } from './apiTags';
import { getToken } from '@/lib/session';
import { loadSessionProfile, saveSessionProfile } from '@/lib/offline/sessionProfile';

/**
 * The single RTK Query slice for the whole application.
 *
 * RTK Query's automatic tag invalidation only works within one API slice, so a
 * shared tag *string* declared in two separate `createApi` slices is inert —
 * each slice keeps its own cache and no refetch ever crosses the boundary. That
 * is why the money domain (sales, purchases, returns, finance, cash flow,
 * expenses, salaries) and the stock domain (products, inventory, stock
 * transfers, audits, price history) were each migrated to this slice first: a
 * mutation that records a sale has to invalidate the tags that the product,
 * inventory, finance and report queries provide.
 *
 * Every feature file keeps its current folder structure and adds its endpoints
 * with `baseApi.injectEndpoints(...)`, re-exporting the generated hooks
 * unchanged — so page-level imports are untouched.
 */

/** The one endpoint whose answer is the session itself. */
const isSessionEndpoint = (url: string): boolean => url.includes('/users/me');

/** Endpoints a caller can reach before they have a token. */
const PUBLIC_ENDPOINTS = ['/login', '/signup'];

const rawBaseQuery = fetchBaseQuery({
  baseUrl: import.meta.env.VITE_BASE_URL,
  prepareHeaders: (headers) => {
    const token = getToken();
    if (token) {
      headers.set('authorization', `Bearer ${token}`);
    }
    return headers;
  },
});

/**
 * A `FetchBaseQueryError` plus the path that produced it.
 *
 * `authListener`'s exempt-list matches on the requested path. RTK Query's
 * rejected payload carries only `{ status, data }`; the url lives inside the
 * queryFn's return value and never reaches the action. Attaching it here is what
 * lets a wrong password (`/users/login` → 401) be told apart from a dead session
 * (`/users/me` → 401), instead of both clearing the token.
 */
type AuthBaseQueryError = FetchBaseQueryError & { url?: string };

/**
 * Answers "no session" locally for `/users/*` when there is no token to send.
 *
 * Thirty components read the logged-in user, including the public navbar. Every
 * one of them would otherwise fire a request that can only ever 401, and on the
 * marketing site that 401 was what made a logged-out visitor look signed-out in
 * a broken way. A synthetic success is the honest answer here: there is no
 * session, so `data.data` is null and callers see exactly what they saw before a
 * token existed.
 */
const baseQueryWithAuth: BaseQueryFn<string | FetchArgs, unknown, AuthBaseQueryError> = async (
  args,
  api,
  extraOptions
) => {
  const url = typeof args === 'string' ? args : (args?.url ?? '');

  if (url.startsWith('/users') && !getToken() && !PUBLIC_ENDPOINTS.some((path) => url.includes(path))) {
    return { data: { success: true, data: null } };
  }

  const result = await rawBaseQuery(args, api, extraOptions);

  if (result.error) {
    // An offline cold start has no in-memory session, so `/users/me` fails with a
    // network error and every dashboard route degrades to an error card or /login —
    // the whole dashboard reads as blank. Serve the last known profile for a
    // *transport* failure only; a real 401/403 must still reach authListener so a
    // revoked token is cleared rather than masked.
    if (isSessionEndpoint(url) && result.error.status === 'FETCH_ERROR') {
      const cached = loadSessionProfile();
      if (cached !== null) return { data: cached };
    }

    return { error: { ...result.error, url } };
  }

  // Remember the session so the next offline load can restore it.
  if (isSessionEndpoint(url)) saveSessionProfile(result.data);

  return result;
};

export const baseApi = createApi({
  reducerPath: 'baseApi',
  baseQuery: baseQueryWithAuth,
  tagTypes: sharedApiTagTypes,
  endpoints: () => ({}),
});
