import { describe, expect, it, vi, afterEach } from 'vitest';

import { ChatbotApiError, fetchAfrodukaPages, searchAfroduka } from './api';

const jsonResponse = (body: unknown, status = 200): Response =>
  new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });

afterEach(() => {
  vi.unstubAllGlobals();
});

describe('fetchAfrodukaPages', () => {
  it('posts the urls to the proxy and returns the extracted pages', async () => {
    const fetchMock = vi.fn(async () =>
      jsonResponse({ results: [{ url: 'https://afroduka.com/', title: 'Home', text: 'Hello' }], errors: [] }),
    );
    vi.stubGlobal('fetch', fetchMock);

    const pages = await fetchAfrodukaPages(['https://afroduka.com/']);

    expect(pages).toEqual([{ url: 'https://afroduka.com/', title: 'Home', text: 'Hello' }]);

    const [url, init] = fetchMock.mock.calls[0] as unknown as [string, RequestInit];
    expect(url).toContain('/chatbot/fetch');
    expect(init.method).toBe('POST');
    expect(JSON.parse(String(init.body))).toEqual({ urls: ['https://afroduka.com/'] });
  });

  it('surfaces the proxy message as a typed error on a non-2xx response', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => jsonResponse({ message: 'I hit a rate limit.' }, 429)));

    await expect(fetchAfrodukaPages(['https://afroduka.com/'])).rejects.toMatchObject({
      name: 'ChatbotApiError',
      message: 'I hit a rate limit.',
      status: 429,
    });
  });

  it('turns a transport failure into a typed, friendly error', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => {
        throw new TypeError('Failed to fetch');
      }),
    );

    await expect(fetchAfrodukaPages(['https://afroduka.com/'])).rejects.toBeInstanceOf(ChatbotApiError);
  });
});

describe('searchAfroduka', () => {
  it('returns ranked hits', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () =>
        jsonResponse({ results: [{ title: 'A', url: 'https://afroduka.com', snippet: 's' }] }),
      ),
    );

    const hits = await searchAfroduka('afroduka pricing');

    expect(hits).toEqual([{ title: 'A', url: 'https://afroduka.com', snippet: 's' }]);
  });
});
