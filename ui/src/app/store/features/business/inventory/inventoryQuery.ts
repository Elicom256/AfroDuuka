import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

export const inventoryQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getInventory: builder.query<any, void>({
      query: () => ({
        url: '/products/',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.inventory),
    }),
  }),
});

export const { useGetInventoryQuery } = inventoryQuery;