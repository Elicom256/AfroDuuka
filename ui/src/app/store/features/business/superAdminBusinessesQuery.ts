import { baseApi } from '@/app/store/baseApi';

export const superAdminBusinessesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getSuperAdminBusinesses: builder.query<any, void>({
      query: () => '/super-admin/businesses',
      providesTags: ['SuperAdminBusinessesAPI'],
    }),
    getSuperAdminBusiness: builder.query<any, number>({
      query: (id) => `/super-admin/businesses/${id}`,
      providesTags: ['SuperAdminBusinessesAPI'],
    }),
    updateBusinessStatus: builder.mutation<any, { id: number; status: string }>({
      query: ({ id, status }) => ({
        url: `/super-admin/businesses/${id}/status`,
        method: 'PATCH',
        body: { status },
      }),
      invalidatesTags: ['SuperAdminBusinessesAPI'],
    }),
  }),
});

export const {
  useGetSuperAdminBusinessesQuery,
  useGetSuperAdminBusinessQuery,
  useUpdateBusinessStatusMutation,
} = superAdminBusinessesQuery;
