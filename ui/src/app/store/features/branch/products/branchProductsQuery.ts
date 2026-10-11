import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const productsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    // get products. Accepts an optional business_branch_id so a screen that offers a
    // branch dropdown (product audits) can pull that branch's products; omitting it
    // keeps the previous no-arg behaviour (the caller's effective scope).
    products: builder.query<any, number | void>({
      query: (business_branch_id) => ({
        url: '/products/',
        method: 'GET',
        params: business_branch_id ? { business_branch_id } : {},
      }),
      providesTags: withDashboard(apiTags.products),
    }),
    // get one product by id
    product: builder.query<any, string>({
      query: (id) => ({
        url: `/products/${id}`,
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.products),
    }),

    // analytics
    productAnalytics: builder.query<any, void>({
      query: () => ({
        url: '/products/analytics',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.products),
    }),

    // expiring products analytics
    productExpiring: builder.query<any, void>({
      query: () => ({
        url: '/products/expiring',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.products),
    }),

    // smart restocking predictions
    productRestocking: builder.query<any, void>({
      query: () => ({
        url: '/products/restocking',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.products),
    }),

    // metrics
    productMetrics: builder.query<any, any>({
      query: ({ id, period = 'last_7_days' }) => ({
        url: `/products/${id}/metrics`,
        method: 'GET',
        params: { period },
      }),
      providesTags: withDashboard(apiTags.products),
    }),
    // A product change alters stock levels, valuations and price history, so the
    // inventory, report and price-history read-models are invalidated with it.
    addProduct: builder.mutation<any, any>({
      query: (body) => ({
        url: `/products/`,
        method: 'POST',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.products, apiTags.inventory, apiTags.priceHistory, apiTags.reports),
    }),

    // update product
    updateProduct: builder.mutation<any, { body: any; id: string }>({
      query: ({ body, id }) => ({
        url: `/products/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: withDashboard(apiTags.products, apiTags.inventory, apiTags.priceHistory, apiTags.reports),
    }),

    // delete product
    deleteProduct: builder.mutation<any, any>({
      query: (id) => ({
        url: `/products/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: withDashboard(apiTags.products, apiTags.inventory, apiTags.priceHistory, apiTags.reports),
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