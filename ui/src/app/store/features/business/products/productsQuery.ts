import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const productCategoriesQuery = createApi({
  reducerPath: 'productCategoriesPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/products`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
        console.log('Available token==>', token);
      }
      return headers;
    },
  }),
  tagTypes: ['ProductCategoriesAPI'],
  endpoints: (builder) => ({
    // ============= PRODUCT CATEGORIES PART ==============
    productCategories: builder.query<any, void>({
      query: () => ({
        url: '/categories',
        method: 'GET',
      }),
      providesTags: ['ProductCategoriesAPI'],
    }),
    productCategory: builder.query<any, string>({
      query: (id) => ({
        url: `/categories/${id}`,
        method: 'GET',
      }),
      providesTags: ['ProductCategoriesAPI'],
    }),
    addProductCategory: builder.mutation<any, any>({
      query: (body) => ({
        url: '/categories',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['ProductCategoriesAPI'],
    }),
    updateProductCategory: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({
        url: `/categories/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['ProductCategoriesAPI'],
    }),
    deleteProductCategory: builder.mutation<any, number>({
      query: (id) => ({
        url: `/categories/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['ProductCategoriesAPI'],
    }),
  }),
});

export const {
  useProductCategoriesQuery,
  useProductCategoryQuery,
  useAddProductCategoryMutation,
  useUpdateProductCategoryMutation,
  useDeleteProductCategoryMutation,
} = productCategoriesQuery;
