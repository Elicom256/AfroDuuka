import { baseApi } from '@/app/store/baseApi';

export const attendanceSettingsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getAttendanceSettings: builder.query<any, void>({
      query: () => ({ url: '/settings/attendance-settings/', method: 'GET' }),
      providesTags: ['AttendanceSettingsAPI'],
    }),
    updateAttendanceSettings: builder.mutation<any, { id: string; body: { status: string } }>({
      query: ({ id, body }) => ({ url: `/settings/attendance-settings/${id}`, method: 'PUT', body }),
      invalidatesTags: ['AttendanceSettingsAPI'],
    }),
  }),
});

export const { useGetAttendanceSettingsQuery, useUpdateAttendanceSettingsMutation } = attendanceSettingsQuery;
