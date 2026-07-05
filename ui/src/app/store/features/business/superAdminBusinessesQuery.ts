import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const superAdminBusinessesQuery = createApi({
  reducerPath: 'superAdminBusinessesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/super-admin`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['SuperAdminBusinessesAPI'],
  endpoints: (builder) => ({
    getSuperAdminBusinesses: builder.query<any, void>({
      query: () => '/businesses',
      providesTags: ['SuperAdminBusinessesAPI'],
    }),
    getSuperAdminBusiness: builder.query<any, number>({
      query: (id) => `/businesses/${id}`,
      providesTags: ['SuperAdminBusinessesAPI'],
    }),
    updateBusinessStatus: builder.mutation<any, { id: number; status: string }>({
      query: ({ id, status }) => ({
        url: `/businesses/${id}/status`,
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
