import { baseApi } from '@/app/store/baseApi';

export const couponsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    coupons: builder.query<any, void>({
      query: () => ({ url: '/coupons/', method: 'GET' }),
      providesTags: ['CouponsAPI'],
    }),
    createCoupon: builder.mutation<any, any>({
      query: (body) => ({ url: '/coupons/', method: 'POST', body }),
      invalidatesTags: ['CouponsAPI'],
    }),
    deleteCoupon: builder.mutation<any, string>({
      query: (id) => ({ url: `/coupons/${id}`, method: 'DELETE' }),
      invalidatesTags: ['CouponsAPI'],
    }),
  }),
});

export const { useCouponsQuery, useCreateCouponMutation, useDeleteCouponMutation } = couponsQuery;
