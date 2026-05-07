import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const rolesQuery = createApi({
  reducerPath: 'rolesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['RolesAPI'],
  endpoints: (builder) => ({
    roles: builder.query<any, void>({
      query: () => ({
        url: '/roles',
        method: 'GET',
      }),
      providesTags: ['RolesAPI'],
    }),
  }),
});

export const { useRolesQuery } = rolesQuery;
