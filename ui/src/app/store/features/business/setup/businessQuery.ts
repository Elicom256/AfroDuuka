import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { getToken } from '@/lib/session';
import type { BusinessCategory } from './onboardingQuery';

export interface Business {
  id: number;
  name: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  logo?: string | null;
  timezone: string;
  status: string;
  business_category_id: number;
  country_id: number;
}

interface BusinessResponse {
  data: Business;
}

export const businessQuery = createApi({
  reducerPath: 'businessPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/dashboard`,
    prepareHeaders: (headers) => {
      const token = getToken();
      if (token) headers.set('authorization', `Bearer ${token}`);
      return headers;
    },
  }),
  tagTypes: ['BusinessApi'],
  endpoints: (builder) => ({
    getBusiness: builder.query<BusinessResponse, void>({
      query: () => '/business',
      providesTags: ['BusinessApi'],
    }),
    registerBusiness: builder.mutation<any, any>({
      query: (body) => ({ url: '/business', method: 'POST', body }),
      invalidatesTags: ['BusinessApi'],
    }),
    updateBusiness: builder.mutation<any, any>({
      query: (body) => ({ url: '/business', method: 'PATCH', body }),
      invalidatesTags: ['BusinessApi'],
    }),
    // Bare array, like the public route this mirrors — see onboardingQuery.
    getBusinessCategories: builder.query<BusinessCategory[], void>({
      query: () => '/business-categories',
      providesTags: ['BusinessApi'],
    }),
  }),
});

export const {
  useGetBusinessQuery,
  useRegisterBusinessMutation,
  useUpdateBusinessMutation,
  useGetBusinessCategoriesQuery,
} = businessQuery;