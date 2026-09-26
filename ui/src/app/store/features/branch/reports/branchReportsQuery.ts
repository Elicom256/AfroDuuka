import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export type MonthlyPerformanceBranch = {
  name: string;
  sales: number;
  purchases: number;
  expenses: number;
  profit_loss: number;
};

export type MonthlyPerformanceReport = {
  business_name: string;
  period: string;
  currency: string;
  figures: {
    sales: number | null;
    purchases: number | null;
    expenses: number | null;
    profit_loss: number | null;
  };
  counts: { sales: number | null; purchases: number | null };
  branches: MonthlyPerformanceBranch[];
  /** YYYY-MM, echoed back so the picker and the document cannot disagree. */
  month: string;
};

type ApiEnvelope<T> = { message: string; data: T };

export const branchReportsQuery = createApi({
  reducerPath: 'reportsApi',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/reports`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');

      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }

      return headers;
    },
  }),
  tagTypes: ['Reports'],
  endpoints: (builder) => ({
    branchPerformance: builder.query<any, { id: string; period: string }>({
      query: ({ id, period }) => ({
        url: `/branch-performance`,
        method: 'GET',
        params: { id, period },
      }),
      providesTags: ['Reports'],
    }),

    stockSummary: builder.query<any, string>({
      query: (period) => ({
        url: '/stock-summary',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    lowStock: builder.query<any, string>({
      query: (period) => ({
        url: '/low-stock',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    outOfStock: builder.query<any, string>({
      query: (period) => ({
        url: '/out-of-stock',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    deadStock: builder.query<any, string>({
      query: (period) => ({
        url: '/dead-stock',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    inventoryValuation: builder.query<any, string>({
      query: (period) => ({
        url: '/inventory-valuation',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    salesByProduct: builder.query<any, string>({
      query: (period) => ({
        url: '/sales-by-product',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),

    stockMovement: builder.query<any, string>({
      query: (period) => ({
        url: '/stock-movement',
        method: 'GET',
        params: { period },
      }),
      providesTags: ['Reports'],
    }),
    // Generic reports list endpoints used elsewhere in the app
    branchReports: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['Reports'],
    }),

    branchReport: builder.query<any, string>({
      query: (id: string) => ({
        url: `/${id}`,
        method: 'GET',
      }),
      providesTags: ['Reports'],
    }),

    // The monthly performance document that the notification email attaches, as
    // figures for the card and as a PDF the user can download.
    //
    // Month-scoped rather than driven by the shared period filter, deliberately: the
    // document is titled with a calendar month and the email's dedupe identity is
    // business:{id}:{YYYY-MM}, so a "last 30 days" version of it would be a different
    // document wearing the same name. The current month is left out of the picker's
    // defaults by the caller because it is still accumulating.
    monthlyPerformance: builder.query<ApiEnvelope<MonthlyPerformanceReport>, string>({
      query: (month) => ({
        url: '/monthly-performance',
        method: 'GET',
        params: { month },
      }),
      providesTags: ['Reports'],
    }),

    // A mutation, not a query, because it must only run when the button is pressed: a
    // report is a few hundred KB of PDF and nobody who came to read the figures asked
    // for that on page load. This is the same shape as downloadReceiptPdf on the
    // receipts page, except that the bytes come back as a Blob rather than base64 —
    // base64 inflates the payload by a third, and this response is never cached in the
    // store the way a preview's would be.
    //
    // RTK's ResponseHandler type has no 'blob' shorthand, so the function form is the
    // supported way to ask for one.
    monthlyPerformancePdf: builder.mutation<Blob, string>({
      query: (month) => ({
        url: '/monthly-performance/pdf',
        method: 'GET',
        params: { month },
        responseHandler: async (response: Response) => response.blob(),
      }),
    }),
  }),
});

export const {
  useBranchPerformanceQuery,
  useStockSummaryQuery,
  useLowStockQuery,
  useOutOfStockQuery,
  useDeadStockQuery,
  useInventoryValuationQuery,
  useSalesByProductQuery,
  useStockMovementQuery,
  useBranchReportsQuery,
  useBranchReportQuery,
  useMonthlyPerformanceQuery,
  useMonthlyPerformancePdfMutation,
} = branchReportsQuery;
