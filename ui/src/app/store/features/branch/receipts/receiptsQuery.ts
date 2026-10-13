import { baseApi } from '@/app/store/baseApi';

export const receiptsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    receipts: builder.query<any, Record<string, any> >({
      query: (params) => ({
        url: '/receipts/',
        method: 'GET',
        params,
      }),
      providesTags: ['ReceiptsAPI'],
    }),
    receipt: builder.query<any, number | string>({
      query: (id) => ({
        url: `/receipts/${id}`,
        method: 'GET',
      }),
      providesTags: ['ReceiptsAPI'],
    }),
    downloadReceiptPdf: builder.mutation<{ pdf: string; filename: string }, number>({
      queryFn: async (id) => {
        const baseUrl = import.meta.env.VITE_BASE_URL || 'http://localhost/api';
        const token = localStorage.getItem('token');
        const response = await fetch(`${baseUrl}/receipts/${id}/pdf`, {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          },
        });
        if (!response.ok) {
          return { error: { status: response.status, data: await response.text() } };
        }
        const data = await response.json();
        return { data };
      },
    }),
  }),
});

export const {
  useReceiptsQuery,
  useReceiptQuery,
  useDownloadReceiptPdfMutation,
} = receiptsQuery;
