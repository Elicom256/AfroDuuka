const TOKEN_KEY = 'token';

export const getToken = (): string | null => localStorage.getItem(TOKEN_KEY);

export const setToken = (token: string): void => localStorage.setItem(TOKEN_KEY, token);

export const clearToken = (): void => localStorage.removeItem(TOKEN_KEY);

/**
 * End the session and send the user back to the login screen.
 *
 * This is a full page navigation on purpose. Once a request has come back 401
 * the token is dead, and the RTK Query cache still holds whatever was fetched
 * with it. A client-side redirect would keep that cache alive and every mounted
 * component would immediately re-issue its query, get another 401, and loop. A
 * hard navigation discards the store with the page. This mirrors what the
 * logout button in UserProfile.tsx already does.
 */
export const endSessionAndRedirect = (): void => {
  clearToken();
  window.location.replace('/login');
};
