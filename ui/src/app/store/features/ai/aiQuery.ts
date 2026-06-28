import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const aiQuery = createApi({
  reducerPath: 'aiPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/ai`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['AiAPI'],
  endpoints: (builder) => ({
    sendAiMessage: builder.mutation<any, { message: string }>({
      query: (body) => ({ url: '/chat', method: 'POST', body }),
      invalidatesTags: ['AiAPI'],
    }),
    getAiTools: builder.query<any, void>({
      query: () => ({ url: '/tools', method: 'GET' }),
      providesTags: ['AiAPI'],
    }),
  }),
});

export const {
  useSendAiMessageMutation,
  useGetAiToolsQuery,
} = aiQuery;
