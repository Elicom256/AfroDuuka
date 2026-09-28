import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const reorderRulesQuery = createApi({
  reducerPath: 'reorderRulesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/reorder-rules`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['ReorderRulesAPI'],
  endpoints: (builder) => ({
    reorderRules: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['ReorderRulesAPI'],
    }),
    reorderRule: builder.query<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'GET' }),
      providesTags: ['ReorderRulesAPI'],
    }),
    createReorderRule: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['ReorderRulesAPI'],
    }),
    updateReorderRule: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ReorderRulesAPI'],
    }),
    deleteReorderRule: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['ReorderRulesAPI'],
    }),
  }),
});

export const {
  useReorderRulesQuery,
  useReorderRuleQuery,
  useCreateReorderRuleMutation,
  useUpdateReorderRuleMutation,
  useDeleteReorderRuleMutation,
} = reorderRulesQuery;
