import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const ordersQuery = createApi({
  reducerPath: 'ordersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/orders`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['OrdersAPI'],
  endpoints: (builder) => ({
    orders: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['OrdersAPI'],
    }),
    order: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['OrdersAPI'],
    }),
    createOrder: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['OrdersAPI'],
    }),
    updateOrder: builder.mutation<any, { id: string; body: any }>({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['OrdersAPI'],
    }),
    deleteOrder: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['OrdersAPI'],
    }),
  }),
});

export const {
  useOrdersQuery,
  useOrderQuery,
  useCreateOrderMutation,
  useUpdateOrderMutation,
  useDeleteOrderMutation,
} = ordersQuery;
