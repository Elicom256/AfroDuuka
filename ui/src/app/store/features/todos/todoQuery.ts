import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const todoQuery = createApi({
  reducerPath: 'todoPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/users/todos`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['TodosAPI'],
  endpoints: (builder) => ({
    getTodos: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['TodosAPI'],
    }),
    createTodo: builder.mutation<any, { title: string; description?: string; date?: string; status: string }>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['TodosAPI'],
    }),
    updateTodo: builder.mutation<
      any,
      { id: number; body: Partial<{ title: string; description: string; date: string; status: string }> }
    >({
      query: ({ id, body }) => ({ url: `/${id}`, method: 'PUT', body }),
      invalidatesTags: ['TodosAPI'],
    }),
    deleteTodo: builder.mutation<any, number>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['TodosAPI'],
    }),
  }),
});

export const { useGetTodosQuery, useCreateTodoMutation, useUpdateTodoMutation, useDeleteTodoMutation } = todoQuery;
