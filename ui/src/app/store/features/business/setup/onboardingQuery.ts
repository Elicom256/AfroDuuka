import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

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
export const onboardingQuery = createApi({
  reducerPath: 'onboardingPath',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}`,
    prepareHeaders: (headers) => {
      const token = localStorage.getItem('token');
      if (token) {
        headers.set('authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['OnboardingApi'],
  endpoints: (builder) => ({
    // Public: safe to call before signup, which is what the form's dropdown needs.
    getPublicBusinessCategories: builder.query<any, void>({
      query: () => '/business-categories',
      providesTags: ['OnboardingApi'],
    }),
    createBusiness: builder.mutation<any, any>({
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
