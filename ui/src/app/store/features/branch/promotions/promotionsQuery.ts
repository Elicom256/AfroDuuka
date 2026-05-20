import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchPromotionsQuery = createApi({
  reducerPath: 'branchPromotionsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/promotions`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchPromotionsAPI'],
  endpoints: (builder) => ({
    branchPromotions: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchPromotionsAPI'],
    }),
  }),
});

export const { useBranchPromotionsQuery } = branchPromotionsQuery;
