/*-----------------------------------------------------------------------------------
 * RTK Query slice: priceHistoryQuery
 * -------------------------------
 * Endpoints for price history data.
 *   - productPriceHistory: product-specific timeline (paginated)
 *   - priceHistoryAnalytics: aggregated price change analytics
 * Base URL points to the main API; requests go to /products/{id}/price-history
 * and /price-history/analytics respectively.
 *---------------------------------------------------------------------------------*/

import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const priceHistoryQuery = createApi({
  reducerPath: 'priceHistoryPath',
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
  tagTypes: ['PriceHistory'],
  endpoints: (builder) => ({
    // Paginated price timeline for a single product
    productPriceHistory: builder.query<any, { productId: string; page?: number; per_page?: number }>({
      query: ({ productId, page = 1, per_page = 15 }) => ({
        url: `/products/${productId}/price-history`,
        method: 'GET',
        params: { page, per_page },
      }),
      providesTags: ['PriceHistory'],
    }),

    // Price change analytics over a period
    priceHistoryAnalytics: builder.query<any, string>({
      query: (period) => ({
        url: `/price-history/analytics`,
        method: 'GET',
        params: { period },
      }),
      providesTags: ['PriceHistory'],
    }),
  }),
});

export const {
  useProductPriceHistoryQuery,
  usePriceHistoryAnalyticsQuery,
} = priceHistoryQuery;
