import { baseApi } from '@/app/store/baseApi';

export const reportExportsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getReportExports: builder.query<any, void>({
      query: () => ({ url: '/report-exports/', method: 'GET' }),
      providesTags: ['ReportExportsAPI'],
    }),
  }),
});

export const { useGetReportExportsQuery } = reportExportsQuery;
