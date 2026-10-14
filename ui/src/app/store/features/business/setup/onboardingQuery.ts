import { baseApi } from '@/app/store/baseApi';

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

/**
 * Reduce any category payload to a plain array.
 *
 * `BusinessCategoryController@index` answers with the collection bare — a JSON
 * array, not `{ data: [...] }` like the country endpoint. The bug this guards was a
 * consumer reading `.data` off that bare array, which is `undefined`: no error, just
 * a silently empty dropdown. The controller returns the same shape for both the
 * public `/business-categories` route and the dashboard one, so every consumer goes
 * through here and no future reader has to guess the wrapping.
 */
export const normalizeBusinessCategories = (payload: unknown): BusinessCategory[] => {
  if (Array.isArray(payload)) return payload as BusinessCategory[];

  if (payload && typeof payload === 'object') {
    const wrapped = payload as { data?: unknown; categories?: unknown };
    if (Array.isArray(wrapped.data)) return wrapped.data as BusinessCategory[];
    if (Array.isArray(wrapped.categories)) return wrapped.categories as BusinessCategory[];
  }

  return [];
};

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

export const onboardingQuery = baseApi.injectEndpoints({
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
