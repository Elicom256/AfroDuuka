import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const salesQuery = createApi({
  reducerPath: 'salesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/orders`,
  }),
  tagTypes: ['SalesAPI'],
  endpoints: (builder) => ({
    orders: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['SalesAPI'],
    }),
    order: builder.query<any, number>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['SalesAPI'],
    }),
    addOrder: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['SalesAPI'],
    }),
    updateOrder: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['SalesAPI'],
    }),
    deleteOrder: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['SalesAPI'],
    }),
  }),
});

export const { useOrdersQuery, useOrderQuery, useAddOrderMutation, useUpdateOrderMutation, useDeleteOrderMutation } =
  salesQuery;
