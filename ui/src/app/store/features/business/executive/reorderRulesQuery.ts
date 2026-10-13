import { baseApi } from '@/app/store/baseApi';

export const reorderRulesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    reorderRules: builder.query<any, void>({
      query: () => ({ url: '/reorder-rules/', method: 'GET' }),
      providesTags: ['ReorderRulesAPI'],
    }),
    reorderRule: builder.query<any, string>({
      query: (id) => ({ url: `/reorder-rules/${id}`, method: 'GET' }),
      providesTags: ['ReorderRulesAPI'],
    }),
    createReorderRule: builder.mutation<any, any>({
      query: (body) => ({ url: '/reorder-rules/', method: 'POST', body }),
      invalidatesTags: ['ReorderRulesAPI'],
    }),
    updateReorderRule: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/reorder-rules/${id}`, method: 'PUT', body }),
      invalidatesTags: ['ReorderRulesAPI'],
    }),
    deleteReorderRule: builder.mutation<any, string>({
      query: (id) => ({ url: `/reorder-rules/${id}`, method: 'DELETE' }),
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
