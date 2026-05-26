import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const customersQuery = createApi({
  reducerPath: 'customersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/customers`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['CustomersAPI'],
  endpoints: (builder) => ({
    customers: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['CustomersAPI'],
    }),
    // customer
    customer: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['CustomersAPI'],
    }),
    // store
    addCustomer: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['CustomersAPI'],
    }),

    // put
    updateCustomer: builder.mutation<any, { body: any; id: string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'DELETE',
        body,
      }),
      invalidatesTags: ['CustomersAPI'],
    }),

    // store
    deleteCustomer: builder.mutation<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['CustomersAPI'],
    }),
  }),
});

export const {
  useCustomersQuery,
  useCustomerQuery,
  useAddCustomerMutation,
  useUpdateCustomerMutation,
  useDeleteCustomerMutation,
} = customersQuery;
