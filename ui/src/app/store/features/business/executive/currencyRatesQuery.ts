import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const currencyRatesQuery = createApi({
  reducerPath: 'currencyRatesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/currency-rates`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['CurrencyRatesAPI'],
  endpoints: (builder) => ({
    currencyRates: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['CurrencyRatesAPI'],
    }),
    currencyRate: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['CurrencyRatesAPI'],
    }),
    createCurrencyRate: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['CurrencyRatesAPI'],
    }),
    updateCurrencyRate: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['CurrencyRatesAPI'],
    }),
    deleteCurrencyRate: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['CurrencyRatesAPI'],
    }),
  }),
});

export const {
  useCurrencyRatesQuery,
  useCurrencyRateQuery,
  useCreateCurrencyRateMutation,
  useUpdateCurrencyRateMutation,
  useDeleteCurrencyRateMutation,
} = currencyRatesQuery;
