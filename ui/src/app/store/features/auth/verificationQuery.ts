import { baseApi } from '@/app/store/baseApi';

/**
 * Business email verification.
 *
 * Two calls, two auth states. `verifyEmail` is public — the whole point of the emailed
 * link is that it works on whatever device opens the inbox, signed in or not. `resend`
 * is authenticated: it mails the address on the caller's own business, so it has to know
 * who is calling. That is why they live here rather than in `onboardingQuery`, which
 * straddles the anonymous-to-signed-in handoff of signup itself.
 */
export interface VerificationResult {
  message: string;
  business?: Record<string, unknown>;
}

export const verificationQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    verifyEmail: builder.query<VerificationResult, string>({
      query: (token) => ({
        url: '/verify-email',
        method: 'GET',
        params: { token },
      }),
      // The token is single-use, so a second call with the same one answers 404. Not
      // caching the result keeps a successful verification from being replayed from
      // cache on a re-render; the page holds its own success state instead.
      keepUnusedDataFor: 0,
    }),
    resendVerification: builder.mutation<VerificationResult, void>({
      query: () => ({
        url: '/verify-email/resend',
        method: 'POST',
      }),
      // A successful resend moves the business's sent-at time and eventually its
      // verified state, both of which `me` reports.
      invalidatesTags: ['UsersAPI'],
    }),
  }),
});

export const { useVerifyEmailQuery, useResendVerificationMutation } = verificationQuery;
