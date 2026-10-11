import { baseApi } from '@/app/store/baseApi';
import { apiTags, withDashboard } from '@/app/store/apiTags';

/**
 * Which way money moved. Mirrors App\Enums\CashFlowDirection on the server, which rejects
 * anything else.
 */
export type CashFlowDirection = 'credit' | 'debit';

/** The repair endpoint's response body. */
type CashFlowRecord = {
  id: number;
  transaction_code: string;
  direction: CashFlowDirection | null;
};

export const cashFlowQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getCashFlows: builder.query<any, number | void>({
      query: (page = 1) => ({ url: `/finances/?page=${page}`, method: 'GET' }),
      providesTags: withDashboard(apiTags.cashflow),
    }),

    /**
     * Record which way the money moved on an adjustment written before a direction was
     * required.
     *
     * Such a row is excluded from the cash balance — the server has no sign for it — which
     * is why the executive dashboard warns about them. Both domains are invalidated: this
     * one for the ledger row itself, and FinanceAPI because `unsigned_adjustments` and
     * `cash_balance` come from the dashboard query, which is what the warning is driven by.
     * Invalidating only the first would leave the warning on screen after a repair that
     * worked.
     */
    setCashFlowDirection: builder.mutation<
      { message: string; data?: CashFlowRecord },
      { id: number; direction: CashFlowDirection }
    >({
      query: ({ id, direction }) => ({
        url: `/finances/adjustments/${id}/direction`,
        method: 'PATCH',
        body: { direction },
      }),
      invalidatesTags: withDashboard(apiTags.cashflow, apiTags.finance),
    }),
  }),
});

export const { useGetCashFlowsQuery, useSetCashFlowDirectionMutation } = cashFlowQuery;