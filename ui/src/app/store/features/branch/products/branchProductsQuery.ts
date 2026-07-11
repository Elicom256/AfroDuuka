import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const productsQuery = createApi({
  reducerPath: 'productsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/products`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['ProductsAPI'],
  endpoints: (builder) => ({
    // get products (no params - uses user's business_branch_id from auth)
    products: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),
    // get one product by id
    product: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),

    // analytics
    productAnalytics: builder.query<any, void>({
      query: () => ({
        url: '/analytics',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),

    // expiring products analytics
    productExpiring: builder.query<any, void>({
      query: () => ({
        url: '/expiring',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),

    // smart restocking predictions
    productRestocking: builder.query<any, void>({
      query: () => ({
        url: '/restocking',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),

    // metrics
    productMetrics: builder.query<any, any>({
      query: ({ id, period = 'last_7_days' }) => ({
        url: `/${id}/metrics`,
        method: 'GET',
        params: { period },
      }),
      providesTags: ['ProductsAPI'],
    }),
    // add product
    addProduct: builder.mutation<any, any>({
      query: (body) => ({
        url: `/`,
        method: 'POST',
        body,
      }),
      invalidatesTags: ['ProductsAPI'],
    }),

    // update product
    updateProduct: builder.mutation<any, { body: any; id: string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['ProductsAPI'],
    }),

    // delete product
    deleteProduct: builder.mutation<any, any>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['ProductsAPI'],
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
