import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const attendanceSettingsQuery = createApi({
  reducerPath: 'attendanceSettingsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/settings/attendance-settings`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['AttendanceSettingsAPI'],
  endpoints: (builder) => ({
    getAttendanceSettings: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['AttendanceSettingsAPI'],
    }),
    updateAttendanceSettings: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'PUT', body }),
      invalidatesTags: ['AttendanceSettingsAPI'],
    }),
  }),
});

export const { useGetAttendanceSettingsQuery, useUpdateAttendanceSettingsMutation } = attendanceSettingsQuery;
