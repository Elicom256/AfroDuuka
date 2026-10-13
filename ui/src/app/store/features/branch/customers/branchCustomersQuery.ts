import { baseApi } from '@/app/store/baseApi';

export const branchCustomersQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    branchCustomers: builder.query<any, void>({
      query: () => ({
        url: '/dashboard/customers/',
        method: 'GET',
      }),
      providesTags: ['BranchCustomersAPI'],
    }),
    branchCustomer: builder.query<any, string>({
      query: (id) => ({
        url: `/dashboard/customers/${id}`,
        method: 'GET',
      }),
      providesTags: ['BranchCustomersAPI'],
    }),
  }),
});

export const { useBranchCustomersQuery, useBranchCustomerQuery } = branchCustomersQuery;
