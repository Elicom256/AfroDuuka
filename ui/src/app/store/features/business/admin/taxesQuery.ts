import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const adminTaxesQuery = createApi({
  reducerPath: 'adminTaxesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/taxes`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['AdminTaxesAPI'],
  endpoints: (builder) => ({
    getAdminTaxes: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['AdminTaxesAPI'],
    }),
  }),
});

export const { useGetAdminTaxesQuery } = adminTaxesQuery;
