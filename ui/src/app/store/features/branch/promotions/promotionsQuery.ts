import { baseApi } from '@/app/store/baseApi';

export const branchPromotionsQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    branchPromotions: builder.query<any, void>({
      query: () => ({
        url: '/promotions/',
        method: 'GET',
      }),
      providesTags: ['BranchPromotionsAPI'],
    }),
  }),
});

export const { useBranchPromotionsQuery } = branchPromotionsQuery;
