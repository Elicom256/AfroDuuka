import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { sharedApiTagTypes } from './apiTags';

/**
 * The single RTK Query slice for every feature that must share tag
 * invalidation: the money domain (sales, purchases, returns, finance, cash
 * flow, expenses, salaries) and the stock domain (products, inventory, stock
 * transfers, audits, price history) feed the read-models (reports and the
 * dashboard / cash flow analytics) that all surface on the executive pages.
 *
 * With one slice, a mutation that records a sale can invalidate tags the
 * product analytics, inventory, finance and report queries provide, and RTK
 * Query refetches all of them automatically. That is exactly what never
 * happened while each feature owned its own `createApi` slice: a shared tag
 * string in two separate slices is inert, because each slice keeps its own
 * cache.
 *
 * Feature files keep their current folder structure and add their endpoints
 * with `baseApi.injectEndpoints(...)`, re-exporting the generated hooks
 * unchanged — so page-level imports are untouched.
 */
export const baseApi = createApi({
  reducerPath: 'baseApi',
  baseQuery: fetchBaseQuery({
    baseUrl: import.meta.env.VITE_BASE_URL,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: sharedApiTagTypes,
  endpoints: () => ({}),
});