import { describe, expect, it, vi, beforeEach } from 'vitest';
import type { ReactNode } from 'react';
import { renderHook, waitFor, act } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

import { apiTags, sharedApiTagTypes, dashboardTags, withDashboard } from './apiTags';

/**
 * Why these tests exist (state.md, Phase 0):
 *
 * The reported bug is that analytics never refresh after a sale/purchase. The
 * mechanism RTK Query relies on is `invalidatesTags` refetching every query
 * that `providesTags` the same tag. That only works inside ONE API slice — a
 * shared tag string in two separate `createApi` slices is inert, which is what
 * the previous commit shipped and why nothing moved on the user's side.
 *
 * The first test pins the registry. The other two prove the mechanism: the same
 * "sale mutation" either leaves the "products" query stale (old per-slice
 * pattern) or refetches it (baseApi + injectEndpoints pattern).
 */

/** A fetch stub that records each request so a refetch is observable. */
const stubFetch = () => {
  const calls = vi.fn(
    async () =>
      new Response(JSON.stringify({ message: 'ok', data: {} }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      })
  );
  return calls;
};

/** fetchBaseQuery may pass a string URL or a Request object; normalise both. */
const urlOf = (call: unknown[]) => {
  const input = call[0] as string | { url?: string };
  if (typeof input === 'string') return input;
  if (input && typeof input === 'object' && typeof input.url === 'string') return input.url;
  return String(input);
};

const providers = (children: ReactNode, store: ReturnType<typeof configureStore>) => (
  <Provider store={store}>{children}</Provider>
);

beforeEach(() => {
  vi.restoreAllMocks();
  localStorage.clear();
});

describe('shared RTK tags registry', () => {
  it('declares every tag the base API slice uses', () => {
    expect(sharedApiTagTypes).toEqual([
      apiTags.products,
      apiTags.inventory,
      apiTags.stockTransfers,
      apiTags.productAudit,
      apiTags.priceHistory,
      apiTags.sales,
      apiTags.purchases,
      apiTags.saleReturns,
      apiTags.purchaseReturns,
      apiTags.finance,
      apiTags.cashflow,
      apiTags.expenses,
      apiTags.salary,
      apiTags.reports,
      apiTags.branches,
      apiTags.dashboard,
    ]);
    expect(apiTags.dashboard).toBe('DashboardAPI');
    expect(withDashboard(apiTags.products)).toEqual([apiTags.products, apiTags.dashboard]);
    expect(withDashboard(apiTags.products, apiTags.dashboard)).toEqual([apiTags.products, apiTags.dashboard]);
    expect(dashboardTags).toEqual([apiTags.dashboard]);
  });
});

describe('cross-domain tag invalidation', () => {
  it('is inert across separate createApi slices (the bug)', async () => {
    const fetchMock = stubFetch();
    vi.stubGlobal('fetch', fetchMock);

    // Old pattern: every feature owns its own slice. Both slices use the SAME
    // tag strings, but each keeps its own cache.
    const productsSlice = createApi({
      reducerPath: 'probeProducts',
      baseQuery: fetchBaseQuery({ baseUrl: 'http://probe/api' }),
      tagTypes: [apiTags.products, apiTags.dashboard],
      endpoints: (builder) => ({
        getProducts: builder.query<any, void>({
          query: () => ({ url: '/products', method: 'GET' }),
          providesTags: withDashboard(apiTags.products),
        }),
      }),
    });
    const salesSlice = createApi({
      reducerPath: 'probeSales',
      baseQuery: fetchBaseQuery({ baseUrl: 'http://probe/api' }),
      tagTypes: [apiTags.sales, apiTags.products, apiTags.dashboard],
      endpoints: (builder) => ({
        addSale: builder.mutation<any, { amount: number }>({
          query: (body) => ({ url: '/sales', method: 'POST', body }),
          invalidatesTags: withDashboard(apiTags.sales, apiTags.products),
        }),
      }),
    });

    const store = configureStore({
      reducer: {
        [productsSlice.reducerPath]: productsSlice.reducer,
        [salesSlice.reducerPath]: salesSlice.reducer,
      },
      middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware().concat(productsSlice.middleware, salesSlice.middleware),
    });

    // Only count the products GET; the mutation POST is a separate request.
    const productCalls = () =>
      fetchMock.mock.calls.filter((call) => urlOf(call).includes('/products')).length;

    renderHook(() => productsSlice.useGetProductsQuery(), {
      wrapper: ({ children }) => providers(children, store),
    });
    await waitFor(() => expect(productCalls()).toBe(1));

    await act(async () => {
      await store.dispatch(salesSlice.endpoints.addSale.initiate({ amount: 200000 }));
    });

    // Give any (correctly) triggered refetch a chance to fire.
    await new Promise<void>((resolve) => setTimeout(resolve, 25));
    expect(productCalls()).toBe(1); // products query stays stale
  });

  it('refetches an injected reader from a mutation in another injected endpoint (baseApi pattern)', async () => {
    const fetchMock = stubFetch();
    vi.stubGlobal('fetch', fetchMock);

    // New pattern: ONE slice for money + stock domains, endpoints injected per
    // feature file exactly as baseApi does.
    const probeApi = createApi({
      reducerPath: 'probeBaseApi',
      baseQuery: fetchBaseQuery({ baseUrl: 'http://probe/api' }),
      tagTypes: sharedApiTagTypes,
      endpoints: () => ({}),
    });
    const productsApi = probeApi.injectEndpoints({
      endpoints: (builder) => ({
        getProducts: builder.query<any, void>({
          query: () => ({ url: '/products', method: 'GET' }),
          providesTags: withDashboard(apiTags.products),
        }),
      }),
    });
    const salesApi = probeApi.injectEndpoints({
      endpoints: (builder) => ({
        addSale: builder.mutation<any, { amount: number }>({
          query: (body) => ({ url: '/sales', method: 'POST', body }),
          invalidatesTags: withDashboard(apiTags.sales, apiTags.products),
        }),
      }),
    });

    const store = configureStore({
      reducer: { [probeApi.reducerPath]: probeApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(probeApi.middleware),
    });

    // Only count the products GET; the mutation POST is a separate request.
    const productCalls = () =>
      fetchMock.mock.calls.filter((call) => urlOf(call).includes('/products')).length;

    renderHook(() => productsApi.useGetProductsQuery(), {
      wrapper: ({ children }) => providers(children, store),
    });
    await waitFor(() => expect(productCalls()).toBe(1));

    await act(async () => {
      await store.dispatch(salesApi.endpoints.addSale.initiate({ amount: 200000 }));
    });

    // The sale mutation invalidates ProductsAPI, so the products query refetches
    // automatically: analytics that derive from stock/money move in step.
    await waitFor(() => expect(productCalls()).toBe(2));
  });
});