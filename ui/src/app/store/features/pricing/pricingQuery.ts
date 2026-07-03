import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

interface Feature {
  name: string;
  description: string;
}

interface Limits {
  maxProducts?: number;
  maxBranches?: number;
  maxUsers?: number;
}

interface Pricing {
  id: number;
  name: string;
  slug: string;
  mark: 'low' | 'most popular' | 'enterprise level';
  description?: string;
  monthly_price: number;
  yearly_price: number;
  features?: string[];
  limits?: Limits;
  is_active?: boolean;
  sort_order?: number;
  currency: string;
}

interface PricingsResponse {
  pricings: Pricing[];
}

export const pricingQuery = createApi({
  reducerPath: 'pricingPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/pricing`,
  }),
  tagTypes: ['PricingAPI'],
  endpoints: (builder) => ({
    getPricings: builder.query<PricingsResponse, void>({
      query: () => ({ url: '/', method: 'GET' }),
      providesTags: ['PricingAPI'],
    }),
  }),
});

export const { useGetPricingsQuery } = pricingQuery;
