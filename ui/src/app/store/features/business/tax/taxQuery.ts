import { baseApi } from '@/app/store/baseApi';

// ==================== TAX CATEGORIES ====================
export const taxCategoriesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    taxCategories: builder.query<any, void>({
      query: () => ({ url: '/tax-categories/', method: 'GET' }),
      providesTags: ['TaxCategoriesAPI'],
    }),
    addTaxCategory: builder.mutation<any, any>({
      query: (body) => ({ url: '/tax-categories/', method: 'POST', body }),
      invalidatesTags: ['TaxCategoriesAPI'],
    }),
    updateTaxCategory: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({ url: `/tax-categories/${id}`, method: 'PUT', body }),
      invalidatesTags: ['TaxCategoriesAPI'],
    }),
    deleteTaxCategory: builder.mutation<any, number>({
      query: (id) => ({ url: `/tax-categories/${id}`, method: 'DELETE' }),
      invalidatesTags: ['TaxCategoriesAPI'],
    }),
  }),
});

export const {
  useTaxCategoriesQuery,
  useAddTaxCategoryMutation,
  useUpdateTaxCategoryMutation,
  useDeleteTaxCategoryMutation,
} = taxCategoriesQuery;

// ==================== TAX RATES ====================
export const taxRatesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    taxRates: builder.query<any, void>({
      query: () => ({ url: '/tax-rates/', method: 'GET' }),
      providesTags: ['TaxRatesAPI'],
    }),
    addTaxRate: builder.mutation<any, any>({
      query: (body) => ({ url: '/tax-rates/', method: 'POST', body }),
      invalidatesTags: ['TaxRatesAPI', 'TaxCategoriesAPI'],
    }),
    updateTaxRate: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({ url: `/tax-rates/${id}`, method: 'PUT', body }),
      invalidatesTags: ['TaxRatesAPI', 'TaxCategoriesAPI'],
    }),
    deleteTaxRate: builder.mutation<any, number>({
      query: (id) => ({ url: `/tax-rates/${id}`, method: 'DELETE' }),
      invalidatesTags: ['TaxRatesAPI', 'TaxCategoriesAPI'],
    }),
  }),
});

export const {
  useTaxRatesQuery,
  useAddTaxRateMutation,
  useUpdateTaxRateMutation,
  useDeleteTaxRateMutation,
} = taxRatesQuery;

// ==================== TAX PAYMENTS ====================
export const taxPaymentsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    taxPayments: builder.query<any, any>({
      query: (params) => ({ url: '/tax-payments/', method: 'GET', params }),
      providesTags: ['TaxPaymentsAPI'],
    }),
    taxPaymentAnalytics: builder.query<any, any>({
      query: (params) => ({ url: '/tax-payments/analytics', method: 'GET', params }),
    }),
    addTaxPayment: builder.mutation<any, any>({
      query: (body) => ({ url: '/tax-payments/', method: 'POST', body }),
      invalidatesTags: ['TaxPaymentsAPI'],
    }),
    updateTaxPayment: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({ url: `/tax-payments/${id}`, method: 'PUT', body }),
      invalidatesTags: ['TaxPaymentsAPI'],
    }),
    deleteTaxPayment: builder.mutation<any, number>({
      query: (id) => ({ url: `/tax-payments/${id}`, method: 'DELETE' }),
      invalidatesTags: ['TaxPaymentsAPI'],
    }),
  }),
});

export const {
  useTaxPaymentsQuery,
  useTaxPaymentAnalyticsQuery,
  useAddTaxPaymentMutation,
  useUpdateTaxPaymentMutation,
  useDeleteTaxPaymentMutation,
} = taxPaymentsQuery;
