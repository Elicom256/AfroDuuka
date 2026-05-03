import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
// authentication for users, here to create accout (admin), get loggedin user, update logged user
export const businessQuery = createApi({
  reducerPath: 'businessPath',
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
  tagTypes: ['BusinessApi'],
  endpoints: (builder) => ({
    // Register mutation
    registerBusiness: builder.mutation<any, any>({
      query: (body) => ({
        url: '/business',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['BusinessApi'],
    }),

    updateBusiness: builder.mutation<any, any>({
      query: (body) => ({
        url: `/update`,
        method: 'PATCH',
        body,
      }),
      invalidatesTags: ['BusinessApi'],
    }),
    getBusinessCategories: builder.query<any, void>({
      query: () => ({
        url: '/business-categories',
        method: 'GET',
      }),
    }),
  }),
});
export const { useRegisterBusinessMutation, useUpdateBusinessMutation, useGetBusinessCategoriesQuery } = businessQuery;
