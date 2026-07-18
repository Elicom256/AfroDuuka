import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const couponsQuery = createApi({
  reducerPath: 'couponsPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/coupons`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['CouponsAPI'],
  endpoints: (builder) => ({
    coupons: builder.query<any, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['CouponsAPI'],
    }),
    createCoupon: builder.mutation<any, any>({
      query: (body) => ({ url: '/', method: 'POST', body }),
      invalidatesTags: ['CouponsAPI'],
    }),
    deleteCoupon: builder.mutation<any, string>({
      query: (id) => ({ url: `/${id}`, method: 'DELETE' }),
      invalidatesTags: ['CouponsAPI'],
    }),
  }),
});

export const { useCouponsQuery, useCreateCouponMutation, useDeleteCouponMutation } = couponsQuery;
