/*-----------------------------------------------------------------------------------
 * RTK Query endpoints: priceHistoryQuery
 * -------------------------------
 * Endpoints for price history data, injected into the shared baseApi so a change
 * that writes price history can invalidate it from another domain.
 *   - productPriceHistory: product-specific timeline (paginated)
 *   - priceHistoryAnalytics: aggregated price change analytics
 *---------------------------------------------------------------------------------*/

import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const priceHistoryQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    // Paginated price timeline for a single product
    productPriceHistory: builder.query<any, { productId: string; page?: number; per_page?: number }>({
      query: ({ productId, page = 1, per_page = 15 }) => ({
        url: `/products/${productId}/price-history`,
        method: 'GET',
        params: { page, per_page },
      }),
      providesTags: withDashboard(apiTags.priceHistory),
    }),

    // Price change analytics over a period
    priceHistoryAnalytics: builder.query<any, string>({
      query: (period) => ({
        url: `/price-history/analytics`,
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.priceHistory),
    }),
  }),
});

export const { useProductPriceHistoryQuery, usePriceHistoryAnalyticsQuery } = priceHistoryQuery;