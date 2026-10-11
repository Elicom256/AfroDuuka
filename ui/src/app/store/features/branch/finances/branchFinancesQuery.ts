import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

// This reads the same /finances feed as financeQuery, so it keys off the finance
// tag rather than a tag of its own: any finance mutation already invalidates it.
export const branchFinancesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    branchFinances: builder.query<any, void>({
      query: () => ({
        url: '/finances/',
        method: 'GET',
      }),
      providesTags: withDashboard(apiTags.finance),
    }),
  }),
});

export const { useBranchFinancesQuery } = branchFinancesQuery;