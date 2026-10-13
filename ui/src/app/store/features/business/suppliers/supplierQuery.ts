import { baseApi } from '@/app/store/baseApi';

// These were once pointed at /admin/suppliers, which no route group serves — every
// supplier endpoint lives under the role-gated /dashboard prefix in routes/executive.php,
// so the old baseUrl silently 404'd and the suppliers page rendered empty for every role.
export const supplierQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    suppliers: builder.query<any, void>({
      query: () => ({
        url: '/dashboard/suppliers/',
        method: 'GET',
      }),
      providesTags: ['SuppliersAPI'],
    }),
    supplier: builder.query<any, string>({
      query: (id) => ({
        url: `/dashboard/suppliers/${id}`,
        method: 'GET',
      }),
      providesTags: ['SuppliersAPI'],
    }),
    addSupplier: builder.mutation<any, any>({
      query: (body) => ({
        url: '/dashboard/suppliers/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['SuppliersAPI'],
    }),
    updateSupplier: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/dashboard/suppliers/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['SuppliersAPI'],
    }),
    deleteSupplier: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/dashboard/suppliers/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['SuppliersAPI'],
    }),
  }),
});

export const {
  useSupplierQuery,
  useSuppliersQuery,
  useAddSupplierMutation,
  useUpdateSupplierMutation,
  useDeleteSupplierMutation,
} = supplierQuery;
