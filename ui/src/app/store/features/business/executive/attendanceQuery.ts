import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const executiveAttendanceQuery = createApi({
  reducerPath: 'executiveAttendancePath',
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
  tagTypes: ['ExecutiveAttendanceAPI'],
  endpoints: (builder) => ({
    employeeAttendances: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['ExecutiveAttendanceAPI'],
    }),

    employeeAttendance: builder.query<any, string>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['ExecutiveAttendanceAPI'],
    }),

    recordEmployeeattendance: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['ExecutiveAttendanceAPI'],
    }),
  }),
});

export const { useEmployeeAttendanceQuery, useEmployeeAttendancesQuery, useRecordEmployeeattendanceMutation } =
  executiveAttendanceQuery;
