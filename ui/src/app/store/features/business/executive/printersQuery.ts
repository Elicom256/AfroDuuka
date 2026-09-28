import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const printersQuery = createApi({
  reducerPath: 'printersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/printers`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['PrintersAPI'],
  endpoints: (builder) => ({
    printers: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['PrintersAPI'],
    }),
    printer: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['PrintersAPI'],
    }),
    createPrinter: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['PrintersAPI'],
    }),
    updatePrinter: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['PrintersAPI'],
    }),
    deletePrinter: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['PrintersAPI'],
    }),
  }),
});

export const {
  usePrintersQuery,
  usePrinterQuery,
  useCreatePrinterMutation,
  useUpdatePrinterMutation,
  useDeletePrinterMutation,
} = printersQuery;
