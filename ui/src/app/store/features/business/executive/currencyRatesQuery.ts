import { baseApi } from '@/app/store/baseApi';

export const currencyRatesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    currencyRates: builder.query<any, void>({
      query: () => ({ url: '/currency-rates/', method: 'GET' }),
      providesTags: ['CurrencyRatesAPI'],
    }),
    currencyRate: builder.query<any, string>({
      query: (id) => ({ url: `/currency-rates/${id}`, method: 'GET' }),
      providesTags: ['CurrencyRatesAPI'],
    }),
    createCurrencyRate: builder.mutation<any, any>({
      query: (body) => ({ url: '/currency-rates/', method: 'POST', body }),
      invalidatesTags: ['CurrencyRatesAPI'],
    }),
    updateCurrencyRate: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/currency-rates/${id}`, method: 'PUT', body }),
      invalidatesTags: ['CurrencyRatesAPI'],
    }),
    deleteCurrencyRate: builder.mutation<any, string>({
      query: (id) => ({ url: `/currency-rates/${id}`, method: 'DELETE' }),
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
