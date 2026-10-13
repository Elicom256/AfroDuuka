import { baseApi } from '@/app/store/baseApi';

/**
 * Authentication for users: create account (admin), the logged-in user, update
 * the logged-in user.
 *
 * The "answer no-session locally and tag the requested path on errors" behaviour
 * that used to live here as a bespoke `baseQuery` now lives in `baseApi` itself
 * (see ./baseApi.ts), because every slice shares that one base query. The urls
 * keep the `/users` prefix so `authListener`'s exempt list still matches them.
 */
export const authQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    /**
     * Arg is `void | { skip?: boolean }` rather than plain `void` so that the two
     * places that mount on every public page — AppRoutes and NavBar — can pass
     * `skip: !hasToken` and avoid asking the server a question they already know
     * the answer to. The union keeps the no-argument call working, which is what the
     * ~25 dashboard components use.
     */
    loggedinUser: builder.query<any, { skip?: boolean } | void>({
      query: () => ({
        url: '/users/me',
        method: 'GET',
      }),
      providesTags: ['UsersAPI'],
    }),
    // Login mutation
    login: builder.mutation({
      query: (credentials) => ({
        url: '/users/login',
        method: 'POST',
        body: credentials,
      }),
      invalidatesTags: ['UsersAPI'],
    }),
    // Logout mutation
    logout: builder.mutation<any, void>({
      query: () => ({
        url: '/users/logout',
        method: 'POST',
      }),
      invalidatesTags: ['UsersAPI'],
    }),
    // Register mutation
    register: builder.mutation({
      query: (userData) => ({
        url: '/users/signup',
        method: 'POST',
        body: userData,
      }),
      invalidatesTags: ['UsersAPI'],
    }),

    updateUser: builder.mutation<any, any>({
      query: (body) => ({
        url: `/users/update`,
        method: 'PATCH',
        body,
      }),
      invalidatesTags: ['UsersAPI'],
    }),

    businessUsers: builder.query<any, void>({
      query: () => ({
        url: '/users/',
        method: 'GET',
      }),
      providesTags: ['UsersAPI'],
    }),
  }),
});
export const {
  useBusinessUsersQuery,
  useLoginMutation,
  useRegisterMutation,
  useLoggedinUserQuery,
  useUpdateUserMutation,
  useLogoutMutation,
} = authQuery;
