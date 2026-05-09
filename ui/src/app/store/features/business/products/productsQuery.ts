import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const productsQuery = createApi({
  reducerPath: 'productsPath',
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
  tagTypes: ['ProductsAPI'],
  endpoints: (builder) => ({
    // ============= PRODUCTS PART ==============
    // get products
    products: builder.query<any, void>({
      query: () => ({
        url: '/business-products',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),
    // get one product by id
    product: builder.query<any, void>({
      query: () => ({
        url: '/business-products',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),
    // create product
    addProduct: builder.mutation<any, void>({
      query: (body) => ({
        url: '/business-products',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['ProductsAPI'],
    }),
    // get one product by id
    updateProduct: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({
        url: `/business-products/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['ProductsAPI'],
    }),
    // get one product by id
    deleteProduct: builder.mutation<any, any>({
      query: (id) => ({
        url: `/business-products/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['ProductsAPI'],
    }),

    // ============= PRODUCTS CATEGORIES PART ==============
    // get products
    productCategories: builder.query<any, void>({
      query: () => ({
        url: '/categories',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),
    // get one product by id
    productCategory: builder.query<any, void>({
      query: () => ({
        url: '/categories',
        method: 'GET',
      }),
      providesTags: ['ProductsAPI'],
    }),
    // create product
    addProductCategory: builder.mutation<any, any>({
      query: (body) => ({
        url: '/categories',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['ProductsAPI'],
    }),
    // get one product by id
    updateProductCategory: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({
        url: `/categories/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['ProductsAPI'],
    }),
    // get one product by id
    deleteProductCategory: builder.mutation<any, void>({
      query: (id) => ({
        url: `/categories/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['ProductsAPI'],
    }),
  }),
});

export const {
  useProductsQuery,
  useProductQuery,
  useAddProductMutation,
  useUpdateProductMutation,
  useDeleteProductMutation,
  useProductCategoriesQuery,
  useProductCategoryQuery,
  useAddProductCategoryMutation,
  useUpdateProductCategoryMutation,
  useDeleteProductCategoryMutation,
} = productsQuery;
