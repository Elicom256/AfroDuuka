import { baseApi } from '@/app/store/baseApi';

export const aiQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    sendAiMessage: builder.mutation<any, { message: string }>({
      query: (body) => ({ url: '/ai/chat', method: 'POST', body }),
      invalidatesTags: ['AiAPI'],
    }),
    getAiTools: builder.query<any, void>({
      query: () => ({ url: '/ai/tools', method: 'GET' }),
      providesTags: ['AiAPI'],
    }),
  }),
});

export const {
  useSendAiMessageMutation,
  useGetAiToolsQuery,
} = aiQuery;
