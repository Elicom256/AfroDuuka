import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const businessQuery = createApi({
  reducerPath: 'businessPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['BusinessApi'],
  endpoints: (builder) => ({
    getBusiness: builder.query<any, void>({
      query: () => '/business',
      providesTags: ['BusinessApi'],
    }),
    registerBusiness: builder.mutation<any, any>({
      query: (body) => ({ url: '/business', method: 'POST', body }),
      invalidatesTags: ['BusinessApi'],
    }),
    updateBusiness: builder.mutation<any, any>({
      query: (body) => ({ url: '/business', method: 'PATCH', body }),
      invalidatesTags: ['BusinessApi'],
    }),
    getBusinessCategories: builder.query<any, void>({
      query: () => '/business-categories',
    }),
  }),
});

export const {
  useGetBusinessQuery,
  useRegisterBusinessMutation,
  useUpdateBusinessMutation,
  useGetBusinessCategoriesQuery,
} = businessQuery;
