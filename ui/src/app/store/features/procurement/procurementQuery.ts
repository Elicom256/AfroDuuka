import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export interface PurchaseOrderItem {
  id: number;
  purchase_order_id: number;
  product_id: number;
  quantity: number;
  received_quantity: number;
  remaining_quantity: number;
  unit_price: number;
  subtotal: number;
  product?: {
    id: number;
    name: string;
    sku: string;
  };
}

export interface PurchaseOrder {
  id: number;
  business_id: number;
  business_branch_id: number;
  user_id: number;
  supplier_id: number | null;
  order_number: string;
  total_amount: number;
  status: 'draft' | 'pending' | 'approved' | 'ordered' | 'partially_received' | 'received' | 'cancelled';
  order_date: string | null;
  expected_delivery_date: string | null;
  approved_by: number | null;
  received_at: string | null;
  received_by: number | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
  items: PurchaseOrderItem[];
  supplier?: {
    id: number;
    company_name: string;
  };
  user?: {
    id: number;
    firstname: string;
    lastname: string;
  };
}

export interface ReorderSuggestion {
  product_id: number;
  product_name: string;
  sku: string;
  current_stock: number;
  reorder_level: number;
  avg_daily_sales: number;
  days_remaining: number;
  suggested_order_quantity: number;
  last_purchase_price: number;
  estimated_order_value: number;
  supplier_id: number | null;
  supplier_name: string | null;
  branch_id: number;
}

export interface ProcurementOverview {
  products_needing_reorder: number;
  critical_stock: number;
  suggested_purchase_value: number;
  pending_purchase_orders: number;
  pending_orders_value: number;
  recently_ordered: PurchaseOrder[];
  recently_received: PurchaseOrder[];
  reorder_suggestions: ReorderSuggestion[];
}

export const procurementApi = createApi({
  reducerPath: 'procurementApi',
  baseQuery: fetchBaseQuery({
    baseUrl: '/api/procurement',
    prepareHeaders: (headers, { getState }) => {
      const token = (getState() as any).auth?.token;
      if (token) {
        headers.set('Authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['Procurement'],
  endpoints: (builder) => ({
    getOverview: builder.query<ProcurementOverview, string | void>({
      query: (branchId) => branchId ? `/?branch_id=${branchId}` : '/',
      providesTags: ['Procurement'],
    }),
    getReorderSuggestions: builder.query<{ suggestions: ReorderSuggestion[]; total_suggestions: number; total_estimated_value: number }, string | void>({
      query: (branchId) => branchId ? `/reorder-suggestions?branch_id=${branchId}` : '/reorder-suggestions',
      providesTags: ['Procurement'],
    }),
    getPurchaseOrders: builder.query<{ data: PurchaseOrder[] }, { status?: string; branch_id?: string } | void>({
      query: (params) => {
        const searchParams = new URLSearchParams();
        if (params?.status) searchParams.set('status', params.status);
        if (params?.branch_id) searchParams.set('branch_id', params.branch_id);
        const qs = searchParams.toString();
        return qs ? `/purchase-orders?${qs}` : '/purchase-orders';
      },
      providesTags: ['Procurement'],
    }),
    getPurchaseOrder: builder.query<PurchaseOrder, number>({
      query: (id) => `/purchase-orders/${id}`,
      providesTags: ['Procurement'],
    }),
    createPurchaseOrder: builder.mutation<PurchaseOrder, any>({
      query: (body) => ({
        url: '/purchase-orders',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['Procurement'],
    }),
    approvePurchaseOrder: builder.mutation<PurchaseOrder, number>({
      query: (id) => ({
        url: `/purchase-orders/${id}/approve`,
        method: 'POST',
      }),
      invalidatesTags: ['Procurement'],
    }),
    orderPurchaseOrder: builder.mutation<PurchaseOrder, number>({
      query: (id) => ({
        url: `/purchase-orders/${id}/order`,
        method: 'POST',
      }),
      invalidatesTags: ['Procurement'],
    }),
    receivePurchaseOrder: builder.mutation<PurchaseOrder, { id: number; items: Array<{ id: number; received_quantity: number }> }>({
      query: ({ id, items }) => ({
        url: `/purchase-orders/${id}/receive`,
        method: 'POST',
        body: { items },
      }),
      invalidatesTags: ['Procurement'],
    }),
    cancelPurchaseOrder: builder.mutation<PurchaseOrder, number>({
      query: (id) => ({
        url: `/purchase-orders/${id}/cancel`,
        method: 'POST',
      }),
      invalidatesTags: ['Procurement'],
    }),
  }),
});

export const {
  useGetOverviewQuery,
  useGetReorderSuggestionsQuery,
  useGetPurchaseOrdersQuery,
  useGetPurchaseOrderQuery,
  useCreatePurchaseOrderMutation,
  useApprovePurchaseOrderMutation,
  useOrderPurchaseOrderMutation,
  useReceivePurchaseOrderMutation,
  useCancelPurchaseOrderMutation,
} = procurementApi;
