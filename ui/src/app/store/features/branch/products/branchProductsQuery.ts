import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const bsbranchProductsQuery = createApi({
  reducerPath: 'branchProductsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/products/business-branch-products`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchProductsAPI'],
  endpoints: (builder) => ({
    // get products
    branchProducts: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchProductsAPI'],
    }),
    // get one product by id
    branchProduct: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['BranchProductsAPI'],
    }),

    // analytics
    branchProductAnalytics: builder.query<any, void>({
      query: () => ({
        url: '/analytics',
        method: 'GET',
      }),
      providesTags: ['BranchProductsAPI'],
    }),

    // expiring products analytics
    branchProductExpiring: builder.query<any, void>({
      query: () => ({
        url: '/expiring',
        method: 'GET',
      }),
      providesTags: ['BranchProductsAPI'],
    }),

    // smart restocking predictions
    branchProductRestocking: builder.query<any, void>({
      query: () => ({
        url: '/restocking',
        method: 'GET',
      }),
      providesTags: ['BranchProductsAPI'],
    }),

    // metrics
    branchProductMetrics: builder.query<any, any>({
      query: ({id, period = 'last_7_days'}) => ({
        url: `/${id}/metrics`,
        method: 'GET',
        params: { period },
      }),
      providesTags: ['BranchProductsAPI'],
    }),
    // get one product by id
    addBranchProduct: builder.mutation<any, any>({
      query: (body) => ({
        url: `/`,
        method: 'POST',
        body,
      }),
      invalidatesTags: ['BranchProductsAPI'],
    }),

    // get one product by id
    updateBranchProduct: builder.mutation<any, { body: any; id: string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['BranchProductsAPI'],
    }),

    // get one product by id
    deleteBranchProduct: builder.mutation<any, any>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['BranchProductsAPI'],
    }),
  }),
});

export const {
  useBranchProductsQuery,
  useAddBranchProductMutation,
  useBranchProductQuery,
  useBranchProductAnalyticsQuery,
  useBranchProductExpiringQuery,
  useBranchProductRestockingQuery,
  useBranchProductMetricsQuery,
  useUpdateBranchProductMutation,
  useDeleteBranchProductMutation,
} = bsbranchProductsQuery;
