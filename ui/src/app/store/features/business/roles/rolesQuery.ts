import { baseApi } from '@/app/store/baseApi';

export const rolesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    roles: builder.query<any, void>({
      query: () => ({
        url: '/dashboard/roles',
        method: 'GET',
      }),
      providesTags: ['RolesAPI'],
    }),
  }),
});

export const { useRolesQuery } = rolesQuery;
