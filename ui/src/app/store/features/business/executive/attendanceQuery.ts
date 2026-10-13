import { baseApi } from '@/app/store/baseApi';

export const executiveAttendanceQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    employeeAttendances: builder.query<any, void>({
      query: () => ({ url: '/dashboard/attendances/', method: 'GET' }),
      providesTags: ['ExecutiveAttendanceAPI'],
    }),

    employeeAttendance: builder.query<any, string>({
      query: () => ({ url: '/dashboard/attendances/', method: 'GET' }),
      providesTags: ['ExecutiveAttendanceAPI'],
    }),

    recordEmployeeattendance: builder.mutation<any, any>({
      query: (body) => ({ url: '/dashboard/attendances/', method: 'POST', body }),
      invalidatesTags: ['ExecutiveAttendanceAPI'],
    }),
  }),
});

export const { useEmployeeAttendanceQuery, useEmployeeAttendancesQuery, useRecordEmployeeattendanceMutation } =
  executiveAttendanceQuery;
