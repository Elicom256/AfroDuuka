import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
// authentication for users, here to create accout (admin), get loggedin user, update logged user
export const authQuery = createApi({
  reducerPath: 'userPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/users`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['UsersAPI'],
  endpoints: (builder) => ({
    loggedinUser: builder.query<any, void>({
      query: () => ({
        url: '/me',
        method: 'GET',
      }),
      providesTags: ['UsersAPI'],
    }),
    // Login mutation
    login: builder.mutation({
      query: (credentials) => ({
        url: '/login',
        method: 'POST',
        body: credentials,
      }),
      invalidatesTags: ['UsersAPI'],
    }),
    // Register mutation
    register: builder.mutation({
      query: (userData) => ({
        url: '/register',
        method: 'POST',
        body: userData,
      }),
      invalidatesTags: ['UsersAPI'],
    }),

    updateUser: builder.mutation<any, any>({
      query: (body) => ({
        url: `/update`,
        method: 'PATCH',
        body,
      }),
      invalidatesTags: ['UsersAPI'],
    }),
  }),
});
export const { useLoginMutation, useRegisterMutation, useLoggedinUserQuery, useUpdateUserMutation } = authQuery;
