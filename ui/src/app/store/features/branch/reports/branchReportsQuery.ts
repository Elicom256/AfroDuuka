import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchReportsQuery = createApi({
  reducerPath: 'reportsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/reports`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');

      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }

      return headers;
    },
  }),
  tagTypes: ['Reports'],
  endpoints: (builder) => ({
    branchPerformance: builder.query<any, { id: string; period: string }>({
      query: ({ id, period }) => ({
        url: `/branch-performance`,
        method: 'GET',
        params: { id, period },
      }),
      providesTags: ['Reports'],
    }),

    stockSummary: builder.query<any, string>({
      query: (period) => ({
        url: '/stock-summary',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    lowStock: builder.query<any, string>({
      query: (period) => ({
        url: '/low-stock',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    outOfStock: builder.query<any, string>({
      query: (period) => ({
        url: '/out-of-stock',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    deadStock: builder.query<any, string>({
      query: (period) => ({
        url: '/dead-stock',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    inventoryValuation: builder.query<any, string>({
      query: (period) => ({
        url: '/inventory-valuation',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    salesByProduct: builder.query<any, string>({
      query: (period) => ({
        url: '/sales-by-product',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    stockMovement: builder.query<any, string>({
      query: (period) => ({
        url: '/stock-movement',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),
    // Generic reports list endpoints used elsewhere in the app
    branchReports: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['Reports'],
    }),

    branchReport: builder.query<any, string>({
      query: (id: string) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['Reports'],
    }),
  }),
});

export const {
  useBranchPerformanceQuery,
  useStockSummaryQuery,
  useLowStockQuery,
  useOutOfStockQuery,
  useDeadStockQuery,
  useInventoryValuationQuery,
  useSalesByProductQuery,
  useStockMovementQuery,
  useBranchReportsQuery,
  useBranchReportQuery,
} = branchReportsQuery;
