import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchAttendanceQuery = createApi({
  reducerPath: 'branchAttendancePath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/admin/attendance`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['BranchAttendanceAPI'],
  endpoints: (builder) => ({
    branchAttendance: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['BranchAttendanceAPI'],
    }),

    recordBranchAttendance: builder.mutation<any, void>({
      query: () => ({
        url: '/',
        method: 'POST',
      }),
      invalidatesTags: ['BranchAttendanceAPI'],
    }),
  }),
});

export const { useBranchAttendanceQuery, useRecordBranchAttendanceMutation } = branchAttendanceQuery;
