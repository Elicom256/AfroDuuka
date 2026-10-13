import { baseApi } from '@/app/store/baseApi';

export const ordersQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    orders: builder.query<any, void>({
      query: () => ({ url: '/sale-orders/', method: 'GET' }),
      providesTags: ['OrdersAPI'],
    }),
    order: builder.query<any, string>({
      query: (id) => ({ url: `/sale-orders/${id}`, method: 'GET' }),
      providesTags: ['OrdersAPI'],
    }),
    createOrder: builder.mutation<any, any>({
      query: (body) => ({ url: '/sale-orders/', method: 'POST', body }),
      invalidatesTags: ['OrdersAPI'],
    }),
    updateOrder: builder.mutation<any, { id: string; body: any }>({
      query: ({ id, body }) => ({ url: `/sale-orders/${id}`, method: 'PUT', body }),
      invalidatesTags: ['OrdersAPI'],
    }),
    deleteOrder: builder.mutation<any, string>({
      query: (id) => ({ url: `/sale-orders/${id}`, method: 'DELETE' }),
      invalidatesTags: ['OrdersAPI'],
    }),
  }),
});

export const { useOrdersQuery, useOrderQuery, useCreateOrderMutation, useUpdateOrderMutation, useDeleteOrderMutation } =
  ordersQuery;
