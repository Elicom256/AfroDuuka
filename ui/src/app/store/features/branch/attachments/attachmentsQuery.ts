import { baseApi } from '@/app/store/baseApi';

export const attachmentsQuery = baseApi.injectEndpoints({
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
