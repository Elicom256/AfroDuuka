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
    employeeAttendances: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['AdminAttendanceAPI'],
    }),

    employeeAttendance: builder.query<any, string>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['AdminAttendanceAPI'],
    }),

    recordEmployeeattendance: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['AdminAttendanceAPI'],
    }),
  }),
});

export const { useEmployeeAttendanceQuery, useEmployeeAttendancesQuery, useRecordEmployeeattendanceMutation } =
  adminAttendanceQuery;
