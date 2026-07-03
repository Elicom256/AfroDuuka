import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

interface Limits {
  maxProducts?: number;
  maxBranches?: number;
  maxUsers?: number;
}

interface Plan {
  id: number;
  name: string;
  slug: string;
  mark: string;
  description?: string;
  monthly_price: number;
  yearly_price: number;
  discount_percentage?: number;
  billing_cycle?: string;
  features?: string[];
  limits?: Limits;
  status?: string;
  is_active?: boolean;
  sort_order?: number;
  currency: string;
}

interface PlansResponse {
  plans: Plan[];
}

export const plansQuery = createApi({
  reducerPath: 'plansPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/plans`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['PlansAPI'],
  endpoints: (builder) => ({
    getPlans: builder.query<PlansResponse, void>({
      query: () => ({ url: '', method: 'GET' }),
      providesTags: ['PlansAPI'],
    }),
  }),
});

export const { useGetPlansQuery } = plansQuery;
