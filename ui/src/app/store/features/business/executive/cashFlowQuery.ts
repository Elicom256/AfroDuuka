import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

/**
 * Which way money moved. Mirrors App\Enums\CashFlowDirection on the server, which rejects
 * anything else.
 */
export type CashFlowDirection = 'credit' | 'debit';

export const cashFlowQuery = createApi({
  reducerPath: 'cashFlowPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/finances`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['CashFlowAPI'],
  endpoints: (builder) => ({
    getCashFlows: builder.query<any, number | void>({
      query: (page = 1) => ({ url: `/?page=${page}`, method: 'GET' }),
      providesTags: ['CashFlowAPI'],
    }),

    /**
     * Record which way the money moved on an adjustment written before a direction was
     * required.
     *
     * Such a row is excluded from the cash balance — the server has no sign for it — which
     * is why the executive dashboard warns about them. Both slices are invalidated: this
     * one for the ledger row itself, and FinanceAPI because `unsigned_adjustments` and
     * `cash_balance` come from the dashboard query, which is what the warning is driven by.
     * Invalidating only the first would leave the warning on screen after a repair that
     * worked.
     */
    setCashFlowDirection: builder.mutation<any, { id: number; direction: CashFlowDirection }>({
      query: ({ id, direction }) => ({
        url: `/adjustments/${id}/direction`,
        method: 'PATCH',
        body: { direction },
      }),
      invalidatesTags: ['CashFlowAPI', 'FinanceAPI'],
    }),
  }),
});

export const { useGetCashFlowsQuery, useSetCashFlowDirectionMutation } = cashFlowQuery;
