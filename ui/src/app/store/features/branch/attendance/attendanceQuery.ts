import { baseApi } from '@/app/store/baseApi';

export const branchAttendanceQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    branchAttendance: builder.query<any, void>({
      query: () => ({
        url: '/dashboard/attendances/',
        method: 'GET',
      }),
      providesTags: ['BranchAttendanceAPI'],
    }),

    recordBranchAttendance: builder.mutation<any, void>({
      query: () => ({
        url: '/dashboard/attendances/',
        method: 'POST',
      }),
      invalidatesTags: ['BranchAttendanceAPI'],
    }),
  }),
});

export const { useBranchAttendanceQuery, useRecordBranchAttendanceMutation } = branchAttendanceQuery;
