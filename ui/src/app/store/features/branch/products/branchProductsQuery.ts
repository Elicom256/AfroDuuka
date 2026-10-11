import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const productsQuery = createApi({
  reducerPath: 'productsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/products`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: [apiTags.products, apiTags.dashboard],
  endpoints: (builder) => ({
    // get products. Accepts an optional business_branch_id so a screen that offers a
    // branch dropdown (product audits) can pull that branch's products; omitting it
    // keeps the previous no-arg behaviour (the caller's effective scope).
    products: builder.query<any, number | void>({
      query: (business_branch_id) => ({
        url: '/',
        method: 'GET',
        params: business_branch_id ? { business_branch_id } : {},
      }),
      providesTags: withDashboard(apiTags.products),
    }),
    // get one product by id
    product: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.products),
    }),

    // analytics
    productAnalytics: builder.query<any, void>({
      query: () => ({
        url: '/analytics',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.products),
    }),

    // expiring products analytics
    productExpiring: builder.query<any, void>({
      query: () => ({
        url: '/expiring',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.products),
    }),

    // smart restocking predictions
    productRestocking: builder.query<any, void>({
      query: () => ({
        url: '/restocking',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.products),
    }),

    // metrics
    productMetrics: builder.query<any, any>({
      query: ({ id, period = 'last_7_days' }) => ({
        url: `/${id}/metrics`,
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.products),
    }),
    // add product
    addProduct: builder.mutation<any, any>({
      query: (body) => ({
        url: `/`,
        method: 'POST',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.products),
    }),

    // update product
    updateProduct: builder.mutation<any, { body: any; id: string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.products),
    }),

    // delete product
    deleteProduct: builder.mutation<any, any>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: withDashboard(apiTags.products),
    }),
  }),
});

export const {
  useProductsQuery,
  useAddProductMutation,
  useProductQuery,
  useProductAnalyticsQuery,
  useProductExpiringQuery,
  useProductRestockingQuery,
  useProductMetricsQuery,
  useUpdateProductMutation,
  useDeleteProductMutation,
} = productsQuery;
