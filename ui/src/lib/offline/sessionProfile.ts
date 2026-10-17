/**
 * Last-known `/users/me` response, kept so the dashboard survives an offline load.
 *
 * The session profile is the only thing standing between an offline cold start and a
 * blank dashboard: `AppRoutes` cannot choose a dashboard tree without a role, and
 * `ProtectedRoutes` refuses to render a child route without a user, so on a network
 * failure both fall through to an error card or `/login`.
 *
 * Deliberately *not* in IndexedDB: this is session context, not the durable outbox,
 * and offline.md §3 keeps tokens/passwords out of IndexedDB. It is stored in
 * localStorage next to the token that already lives there, it holds no secrets of its
 * own, and it is treated as evictable device state rather than a backup. A real
 * 401/403 still reaches the error path untouched — only a *network* failure (the
 * server could not be reached) falls back to this, so a revoked session is never
 * papered over while online.
 */

const KEY = 'afroduuka.session.profile';

/** Read the cached profile, tolerating an absent or unreadable entry. */
export function loadSessionProfile(): unknown | null {
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return null;
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

/** Persist a successful `/users/me` body. */
export function saveSessionProfile(data: unknown): void {
  if (data === null || data === undefined) return;
  try {
    localStorage.setItem(KEY, JSON.stringify(data));
  } catch {
    // Storage full or unavailable — a failed cache write must never break a request.
  }
}

/** Drop the cached profile (logout). */
export function clearSessionProfile(): void {
  try {
    localStorage.removeItem(KEY);
  } catch {
    // Nothing to recover from.
  }
}
