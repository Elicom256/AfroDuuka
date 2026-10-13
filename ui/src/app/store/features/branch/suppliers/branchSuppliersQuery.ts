import { baseApi } from '@/app/store/baseApi';

export const branchSuppliersQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    branchSuppliers: builder.query<any, void>({
      query: () => ({
        url: '/dashboard/suppliers/',
        method: 'GET',
      }),
      providesTags: ['BranchSuppliersAPI'],
    }),
    branchSupplier: builder.query<any, string>({
      query: (id) => ({
        url: `/dashboard/suppliers/${id}`,
        method: 'GET',
      }),
      providesTags: ['BranchSuppliersAPI'],
    }),
  }),
});

export const { useBranchSuppliersQuery, useBranchSupplierQuery } = branchSuppliersQuery;
