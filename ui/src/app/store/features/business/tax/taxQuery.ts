import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

const prepareHeaders = (headers: Headers) => {
  const token = localStorage.getItem('token');
  if (token) {
    headers.set('authorization', `Bearer ${token}`);
  }
  return headers;
};

// ==================== TAX CATEGORIES ====================
export const taxCategoriesQuery = createApi({
  reducerPath: 'taxCategoriesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/tax-categories`,
    prepareHeaders,
  }),
  tagTypes: ['TaxCategoriesAPI'],
  endpoints: (builder) => ({
    taxCategories: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['TaxCategoriesAPI'],
    }),
    addTaxCategory: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['TaxCategoriesAPI'],
    }),
    updateTaxCategory: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['TaxCategoriesAPI'],
    }),
    deleteTaxCategory: builder.mutation<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
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
export const taxRatesQuery = createApi({
  reducerPath: 'taxRatesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/tax-rates`,
    prepareHeaders,
  }),
  tagTypes: ['TaxRatesAPI', 'TaxCategoriesAPI'],
  endpoints: (builder) => ({
    taxRates: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['TaxRatesAPI'],
    }),
    addTaxRate: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['TaxRatesAPI', 'TaxCategoriesAPI'],
    }),
    updateTaxRate: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['TaxRatesAPI', 'TaxCategoriesAPI'],
    }),
    deleteTaxRate: builder.mutation<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
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
export const taxPaymentsQuery = createApi({
  reducerPath: 'taxPaymentsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/tax-payments`,
    prepareHeaders,
  }),
  tagTypes: ['TaxPaymentsAPI'],
  endpoints: (builder) => ({
    taxPayments: builder.query<any, any>({
      query: (params) => ({ url: '/', method: 'GET', params }),
      providesTags: ['TaxPaymentsAPI'],
    }),
    taxPaymentAnalytics: builder.query<any, any>({
      query: (params) => ({ url: '/analytics', method: 'GET', params }),
    }),
    addTaxPayment: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['TaxPaymentsAPI'],
    }),
    updateTaxPayment: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['TaxPaymentsAPI'],
    }),
    deleteTaxPayment: builder.mutation<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
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