import { baseApi } from '@/app/store/baseApi';

export const printersQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    printers: builder.query<any, void>({
      query: () => ({ url: '/printers/', method: 'GET' }),
      providesTags: ['PrintersAPI'],
    }),
    printer: builder.query<any, string>({
      query: (id) => ({ url: `/printers/${id}`, method: 'GET' }),
      providesTags: ['PrintersAPI'],
    }),
    createPrinter: builder.mutation<any, any>({
      query: (body) => ({ url: '/printers/', method: 'POST', body }),
      invalidatesTags: ['PrintersAPI'],
    }),
    updatePrinter: builder.mutation<any, any>({
      query: ({ id, ...body }) => ({ url: `/printers/${id}`, method: 'PUT', body }),
      invalidatesTags: ['PrintersAPI'],
    }),
    deletePrinter: builder.mutation<any, string>({
      query: (id) => ({ url: `/printers/${id}`, method: 'DELETE' }),
      invalidatesTags: ['PrintersAPI'],
    }),
  }),
});

export const {
  usePrintersQuery,
  usePrinterQuery,
  useCreatePrinterMutation,
  useUpdatePrinterMutation,
  useDeletePrinterMutation,
} = printersQuery;
