import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const adminAttendanceQuery = createApi({
  reducerPath: 'adminAttendancePath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/attendances`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['AdminAttendanceAPI'],
  endpoints: (builder) => ({
    getAdminAttendance: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['AdminAttendanceAPI'],
    }),
  }),
});

export const { useGetAdminAttendanceQuery } = adminAttendanceQuery;
