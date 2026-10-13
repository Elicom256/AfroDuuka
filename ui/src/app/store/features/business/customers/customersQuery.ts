import { baseApi } from '@/app/store/baseApi';

export const customersQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    customers: builder.query<any, void>({
      query: () => ({
        url: '/dashboard/customers/',
        method: 'GET',
      }),
      providesTags: ['CustomersAPI'],
    }),
    // customer
    customer: builder.query<any, string>({
      query: (id) => ({
        url: `/dashboard/customers/${id}`,
        method: 'GET',
      }),
      providesTags: ['CustomersAPI'],
    }),
    // store
    addCustomer: builder.mutation<any, any>({
      query: (body) => ({
        url: '/dashboard/customers/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['CustomersAPI'],
    }),

    // put
    updateCustomer: builder.mutation<any, { body: any; id: string }>({
      query: ({ body, id }) => ({
        url: `/dashboard/customers/${id}`,
        method: 'PATCH',
        body,
      }),
      invalidatesTags: ['CustomersAPI'],
    }),

    // store
    deleteCustomer: builder.mutation<any, string>({
      query: (id) => ({
        url: `/dashboard/customers/${id}`,
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
