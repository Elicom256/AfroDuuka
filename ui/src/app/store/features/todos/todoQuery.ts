import { baseApi } from '@/app/store/baseApi';

export const todoQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getTodos: builder.query<any, void>({
      query: () => ({ url: '/users/todos/', method: 'GET' }),
      providesTags: ['TodosAPI'],
    }),
    createTodo: builder.mutation<any, { title: string; description?: string; date?: string; status: string }>({
      query: (body) => ({ url: '/users/todos/', method: 'POST', body }),
      invalidatesTags: ['TodosAPI'],
    }),
    updateTodo: builder.mutation<
      any,
      { id: number; body: Partial<{ title: string; description: string; date: string; status: string }> }
    >({
      query: ({ id, body }) => ({ url: `/users/todos/${id}`, method: 'PUT', body }),
      invalidatesTags: ['TodosAPI'],
    }),
    deleteTodo: builder.mutation<any, number>({
      query: (id) => ({ url: `/users/todos/${id}`, method: 'DELETE' }),
      invalidatesTags: ['TodosAPI'],
    }),
  }),
});

export const { useGetTodosQuery, useCreateTodoMutation, useUpdateTodoMutation, useDeleteTodoMutation } = todoQuery;
