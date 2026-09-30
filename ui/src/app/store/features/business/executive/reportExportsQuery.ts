import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const reportExportsQuery = createApi({
  reducerPath: 'reportExportsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/report-exports`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['ReportExportsAPI'],
  endpoints: (builder) => ({
    getReportExports: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['ReportExportsAPI'],
    }),
  }),
});

export const { useGetReportExportsQuery } = reportExportsQuery;
