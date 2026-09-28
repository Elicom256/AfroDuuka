import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const cashFlowQuery = createApi({
  reducerPath: 'cashFlowPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/finances`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['CashFlowAPI'],
  endpoints: (builder) => ({
    getCashFlows: builder.query<any, number | void>({
      query: (page = 1) => ({ url: `/?page=${page}`, method: 'GET' }),
      providesTags: ['CashFlowAPI'],
    }),
  }),
});

export const { useGetCashFlowsQuery } = cashFlowQuery;
