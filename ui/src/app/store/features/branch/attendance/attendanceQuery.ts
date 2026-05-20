import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const branchAttendanceQuery = createApi({
  reducerPath: 'branchAttendancePath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/attendance`,
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
  }),
});

export const { useBranchAttendanceQuery } = branchAttendanceQuery;
