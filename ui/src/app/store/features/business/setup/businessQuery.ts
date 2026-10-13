import { baseApi } from '@/app/store/baseApi';
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

export const businessQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getBusiness: builder.query<BusinessResponse, void>({
      query: () => '/dashboard/business',
      providesTags: ['BusinessApi'],
    }),
    registerBusiness: builder.mutation<any, any>({
      query: (body) => ({ url: '/dashboard/business', method: 'POST', body }),
      invalidatesTags: ['BusinessApi'],
    }),
    updateBusiness: builder.mutation<any, any>({
      query: (body) => ({ url: '/dashboard/business', method: 'PATCH', body }),
      invalidatesTags: ['BusinessApi'],
    }),
    // Bare array, like the public route this mirrors — see onboardingQuery.
    getBusinessCategories: builder.query<BusinessCategory[], void>({
      query: () => '/dashboard/business-categories',
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
