import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const attachmentsQuery = createApi({
  reducerPath: 'attachmentsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: import.meta.env.VITE_BASE_URL,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['ProductAttachments'],
  endpoints: (builder) => ({
    getProductAttachments: builder.query<any, string | number>({
      query: (productId) => ({
        url: `/products/${productId}/attachments`,
        method: 'GET',
      }),
      providesTags: ['ProductAttachments'],
    }),
    uploadProductAttachment: builder.mutation<any, { productId: string | number; file: File }>({
      query: ({ productId, file }) => {
        const formData = new FormData();
        formData.append('file', file);
        return {
          url: `/products/${productId}/attachments`,
          method: 'POST',
          body: formData,
        };
      },
      invalidatesTags: ['ProductAttachments'],
    }),
    deleteProductAttachment: builder.mutation<any, { productId: string | number; attachmentId: number }>({
      query: ({ productId, attachmentId }) => ({
        url: `/products/${productId}/attachments/${attachmentId}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['ProductAttachments'],
    }),
  }),
});

export const {
  useGetProductAttachmentsQuery,
  useUploadProductAttachmentMutation,
  useDeleteProductAttachmentMutation,
} = attachmentsQuery;