/**
 * The landing chatbot's network layer.
 *
 * Plain `fetch` rather than the app's shared RTK Query slice, and deliberately so. This
 * is a public, unauthenticated widget: the shared slice carries the session token and,
 * more importantly, its error middleware raises a global toast for every failed request.
 * A TinyFish rate limit would then put an error toast over the marketing page for a
 * failure the conversation already explains in place. Keeping the calls here also means
 * the chatbot never depends on auth or session state.
 *
 * Only the two server-side proxy routes are called; the TinyFish key lives on the server
 * and is never reachable from here.
 */

export type FetchedPage = {
  url: string;
  title: string;
  text: string;
};

export type SearchHit = {
  title: string;
  url: string;
  snippet: string;
};

/** An error carrying the proxy's own human-readable message and its HTTP status. */
export class ChatbotApiError extends Error {
  readonly status: number;

  constructor(message: string, status: number) {
    super(message);
    this.name = 'ChatbotApiError';
    this.status = status;
  }
}

const baseUrl = (): string => (import.meta.env.VITE_BASE_URL ?? '').replace(/\/+$/, '');

async function post<T>(path: string, body: unknown): Promise<T> {
  let response: Response;

  try {
    response = await fetch(`${baseUrl()}${path}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(body),
    });
  } catch {
    // DNS failure, offline, CORS, or an aborted request — no HTTP answer ever arrived.
    throw new ChatbotApiError('I could not reach the server. Check your connection and try again.', 0);
  }

  const payload = (await response.json().catch(() => null)) as { message?: unknown } | null;

  if (!response.ok) {
    const message = typeof payload?.message === 'string' ? payload.message : 'The web lookup did not succeed.';
    throw new ChatbotApiError(message, response.status);
  }

  return payload as T;
}

/** Read clean content from public AfroDuuka pages through the server-side proxy. */
export async function fetchAfrodukaPages(urls: string[]): Promise<FetchedPage[]> {
  const payload = await post<{ results?: FetchedPage[] }>('/chatbot/fetch', { urls });

  return (payload.results ?? []).map((page) => ({
    url: page.url,
    title: page.title,
    text: page.text ?? '',
  }));
}

/** Ranked web results, used only when a page yields nothing relevant. */
export async function searchAfroduka(query: string): Promise<SearchHit[]> {
  const payload = await post<{ results?: SearchHit[] }>('/chatbot/search', { query });

  return (payload.results ?? []).map((hit) => ({
    title: hit.title,
    url: hit.url,
    snippet: hit.snippet ?? '',
  }));
}
