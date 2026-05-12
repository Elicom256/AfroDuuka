import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const supplierQuery = createApi({
  reducerPath: 'suppliersPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/suppliers`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['SuppliersAPI'],
  endpoints: (builder) => ({
    suppliers: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['SuppliersAPI'],
    }),
    supplier: builder.query<any, string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['SuppliersAPI'],
    }),
    addSupplier: builder.mutation<any, any>({
      query: (body) => ({
        url: '/',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['SuppliersAPI'],
    }),
    updateSupplier: builder.mutation<any, { body: any; id: number | string }>({
      query: ({ body, id }) => ({
        url: `/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['SuppliersAPI'],
    }),
    deleteSupplier: builder.mutation<any, number | string>({
      query: (id) => ({
        url: `/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['SuppliersAPI'],
    }),
  }),
});

export const {
  useSupplierQuery,
  useSuppliersQuery,
  useAddSupplierMutation,
  useUpdateSupplierMutation,
  useDeleteSupplierMutation,
} = supplierQuery;
