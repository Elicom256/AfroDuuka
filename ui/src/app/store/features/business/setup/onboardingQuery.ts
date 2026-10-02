import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { getToken } from '@/lib/session';

/**
 * Self-serve signup onboarding.
 *
 * Kept separate from businessQuery because signup is the one flow that straddles two
 * different auth states: reading business categories happens while still anonymous,
 * while creating the business happens with a token obtained moments earlier. The public
 * categories route is `api/business-categories` (no role middleware, because a global
 * reference table leaks nothing); the business write lives under `api/dashboard`, which
 * RequireBusiness permits only because the caller has no business yet.
 */
export interface BusinessCategory {
  id: number;
  name: string;
  description?: string | null;
}

export interface CreateBusinessBranch {
  name: string;
  address?: string;
  phone?: string;
}

export interface CreateBusinessArgs {
  name: string;
  business_category_id: number;
  country_id: number;
  email?: string;
  phone?: string;
  address?: string;
  /**
   * Sent with the business rather than after it. The onboarding form collects branches
   * as a step, and a separate request per branch left the tenant half-built whenever one
   * of them was rejected — most easily by two branches sharing a name, which the
   * unique (business_id, name) index refuses.
   */
  branches?: CreateBusinessBranch[];
}

export const onboardingQuery = createApi({
  reducerPath: 'onboardingPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}`,
    prepareHeaders: (headers) => {
      const token = getToken();
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['OnboardingApi'],
  endpoints: (builder) => ({
    // Public: safe to call before signup, which is what the form's dropdown needs.
    // The controller returns the collection bare — not wrapped in a `data` key — so
    // this endpoint's type is the array itself. Reading `.data` off it is what left
    // the category dropdown permanently empty.
    getPublicBusinessCategories: builder.query<BusinessCategory[], void>({
      query: () => '/business-categories',
      providesTags: ['OnboardingApi'],
    }),
    createBusiness: builder.mutation<any, CreateBusinessArgs>({
      query: (body) => ({
        url: '/dashboard/business',
        method: 'POST',
        body,
      }),
      invalidatesTags: ['OnboardingApi'],
    }),
  }),
});

export const { useGetPublicBusinessCategoriesQuery, useCreateBusinessMutation } = onboardingQuery;