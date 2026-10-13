import { baseApi } from '@/app/store/baseApi';

export const productCategoriesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    // ============= PRODUCT CATEGORIES PART ==============
    productCategories: builder.query<any, void>({
      query: () => ({
        url: '/products/categories',
        method: 'GET',
      }),
      providesTags: ['ProductCategoriesAPI'],
    }),
    productCategory: builder.query<any, string>({
      query: (id) => ({
        url: `/products/categories/${id}`,
        method: 'GET',
      }),
      providesTags: ['ProductCategoriesAPI'],
    }),
    addProductCategory: builder.mutation<any, any>({
      query: (body) => ({
        url: '/products/categories',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['ProductCategoriesAPI'],
    }),
    updateProductCategory: builder.mutation<any, { body: any; id: number }>({
      query: ({ body, id }) => ({
        url: `/products/categories/${id}`,
        method: 'PUT',
        body,
      }),
      invalidatesTags: ['ProductCategoriesAPI'],
    }),
    deleteProductCategory: builder.mutation<any, number>({
      query: (id) => ({
        url: `/products/categories/${id}`,
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
