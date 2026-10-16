/**
 * The address a verification link was just sent to, remembered across the
 * onboarding -> /verify-email navigation.
 *
 * Router state would be enough for a client-side navigation, but the check-inbox page
 * is also reached by a full page load (a refresh, or the email link itself), and state
 * does not survive that. sessionStorage does, without outliving the tab the way
 * localStorage would. It is never the source of truth — `me` is — only a fast path so
 * the first render can name the address before the query answers.
 */
const STORAGE_KEY = 'afroduuka:pending-verification-email';

/**
 * How long the resend affordance stays disabled after a send.
 *
 * Matches the API's per-business cooldown, so the UI and the server agree and a
 * double-click cannot produce a "sent" toast followed by a silently dropped mail.
 * Shared here so the check-inbox page and the dashboard banner cannot drift apart.
 */
export const RESEND_COOLDOWN_SECONDS = 60;

export const rememberVerificationEmail = (email: string): void => {
  try {
    sessionStorage.setItem(STORAGE_KEY, email);
  } catch {
    // Storage disabled or full is not worth interrupting the flow for; the address
    // still arrives from router state or `me`.
  }
};

export const readVerificationEmail = (): string | null => {
  try {
    return sessionStorage.getItem(STORAGE_KEY);
  } catch {
    return null;
  }
};

export const clearVerificationEmail = (): void => {
  try {
    sessionStorage.removeItem(STORAGE_KEY);
  } catch {
    // Nothing to do.
  }
};
