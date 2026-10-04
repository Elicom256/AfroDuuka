import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import type { BaseQueryFn, FetchArgs, FetchBaseQueryError } from '@reduxjs/toolkit/query';
import { getToken } from '@/lib/session';

/** Endpoints a caller can reach before they have a token. */
const PUBLIC_ENDPOINTS = ['/login', '/signup'];

const rawBaseQuery = fetchBaseQuery({
  baseUrl: `${import.meta.env.VITE_BASE_URL}/users`,
  prepareHeaders: (headers) => {
    const token = getToken();
    if (token) {
      headers.set('authorization', `Bearer ${token}`);
      // Token is attached silently
    }
    return headers;
  },
});

/**
 * Refuses to ask /users/me without a token, and answers "no session" locally
 * instead.
 *
 * Thirty components read the logged-in user, including the public navbar. Every
 * one of them would otherwise fire a request that can only ever 401, and on the
 * marketing site that 401 was what made a logged-out visitor look signed-out in
 * a broken way. A synthetic success is the honest answer here: there is no
 * session, so `data.data` is null and callers see exactly what they saw before a
 * token existed.
 */
/**
 * `FetchBaseQueryError` plus the path that produced it.
 *
 * See the comment on authBaseQuery below for why the url has to be carried along
 * with the error rather than read off the rejected action.
 */
type AuthBaseQueryError = FetchBaseQueryError & { url?: string };

const authBaseQuery: BaseQueryFn<string | FetchArgs, unknown, AuthBaseQueryError> = async (args, api, extraOptions) => {
  const url = typeof args === 'string' ? args : (args?.url ?? '');

  if (!getToken() && !PUBLIC_ENDPOINTS.some((path) => url.includes(path))) {
    return { data: { success: true, data: null } };
  }

  const result = await rawBaseQuery(args, api, extraOptions);

  // Attach the requested path to the error so authListener's exempt-list can match
  // on it. RTK Query's rejected payload carries only { status, data }; the url lives
  // inside the queryFn's return value and never reaches the action. That made
  // `payload.url` permanently undefined, so the exempt-list compared '' against
  // '/users/login', never matched, and a wrong password looked identical to a dead
  // session. Nothing was lost while the listener was unregistered, but it is the
  // next thing to bite now that it is live.
  if (result.error) {
    return { error: { ...result.error, url } };
  }

  return result;
};

// authentication for users, here to create accout (admin), get loggedin user, update logged user
export const authQuery = createApi({
  reducerPath: 'userPath',
  baseQuery: authBaseQuery,
  tagTypes: ['UsersAPI'],
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
        url: '/me',
        method: 'GET',
      }),
      providesTags: ['UsersAPI'],
    }),
    // Login mutation
    login: builder.mutation({
      query: (credentials) => ({
        url: '/login',
        method: 'POST',
        body: credentials,
      }),
      invalidatesTags: ['UsersAPI'],
    }),
    // Logout mutation
    logout: builder.mutation<any, void>({
      query: () => ({
        url: '/logout',
        method: 'POST',
      }),
      invalidatesTags: ['UsersAPI'],
    }),
    // Register mutation
    register: builder.mutation({
      query: (userData) => ({
        url: '/signup',
        method: 'POST',
        body: userData,
      }),
      invalidatesTags: ['UsersAPI'],
    }),

    updateUser: builder.mutation<any, any>({
      query: (body) => ({
        url: `/update`,
        method: 'PATCH',
        body,
      }),
      invalidatesTags: ['UsersAPI'],
    }),

    businessUsers: builder.query<any, void>({
      query: () => ({
        url: '/',
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
