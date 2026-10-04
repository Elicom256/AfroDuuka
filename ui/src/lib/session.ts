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
 * hard navigation discards the store with the page. UserProfile.handleLogout does
 * the same navigation, for the same reason, but calls clearToken() directly since
 * it has no rejection to react to.
 *
 * Called from authListener, which is registered in the store — see store.ts. That
 * listener used to exist without being wired up, so this function never ran and no
 * dead token was ever cleared.
 */
export const endSessionAndRedirect = (): void => {
  clearToken();
  window.location.replace('/login');
};
