import { baseApi } from '@/app/store/baseApi';

export const posQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    searchProducts: builder.query<any, string>({
      query: (q) => ({
        url: `/pos/products/search`,
        method: 'GET',
        params: { q },
      }),
    }),
    searchProductByBarcode: builder.query<any, string>({
      query: (code) => ({
        url: `/pos/products/by-barcode/${encodeURIComponent(code.trim())}`,
        method: 'GET',
      }),
    }),
    searchCustomers: builder.query<any, string>({
      query: (q) => ({
        url: `/pos/customers/search`,
        method: 'GET',
        params: { q },
      }),
    }),
    validateCart: builder.mutation<any, any>({
      query: (body) => ({
        url: '/pos/cart/validate',
        method: 'POST',
        body,
      }),
    }),
    checkout: builder.mutation<any, any>({
      query: (body) => ({
        url: '/pos/checkout',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['PosAPI', 'HeldSales'],
    }),
    holdSale: builder.mutation<any, any>({
      query: (body) => ({
        url: '/pos/sales/hold',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['HeldSales'],
    }),
    getHeldSales: builder.query<any, void>({
      query: () => ({
        url: '/pos/sales/held',
        method: 'GET',
      }),
      providesTags: ['HeldSales'],
    }),
    resumeHeldSale: builder.query<any, number>({
      query: (id) => ({
        url: `/pos/sales/held/${id}`,
        method: 'GET',
      }),
      providesTags: ['HeldSales'],
    }),
    deleteHeldSale: builder.mutation<any, number>({
      query: (id) => ({
        url: `/pos/sales/held/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['HeldSales'],
    }),
  }),
});

export const {
  useLazySearchProductsQuery,
  useLazySearchProductByBarcodeQuery,
  useLazySearchCustomersQuery,
  useValidateCartMutation,
  useCheckoutMutation,
  useHoldSaleMutation,
  useGetHeldSalesQuery,
  useLazyResumeHeldSaleQuery,
  useDeleteHeldSaleMutation,
} = posQuery;
