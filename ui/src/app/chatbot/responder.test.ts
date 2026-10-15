import { describe, expect, it, vi } from 'vitest';

import { answerQuestion, extractPassages, matchEntry, questionTerms, type ChatbotDeps } from './responder';
import { AFRODUKA_PAGES, SUGGESTED_QUESTIONS } from './knowledge';

/**
 * The routing decision is what keeps TinyFish calls down to the topics that actually
 * need the live web. These pin both halves: verified questions stay local (no request),
 * and the unverified ones do make a genuine retrieval request.
 */

const deps = (overrides: Partial<ChatbotDeps> = {}): ChatbotDeps => ({
  fetchPages: vi.fn(async () => []),
  search: vi.fn(async () => []),
  ...overrides,
});

describe('matchEntry', () => {
  it('routes each suggested question to a topic', () => {
    for (const question of SUGGESTED_QUESTIONS) {
      expect(matchEntry(question), question).not.toBeNull();
    }
  });

  it('prefers the specific topic over an incidental keyword', () => {
    // "afroduka" alone would match the overview; "what features" is the stronger signal.
    expect(matchEntry('What features does it offer?')?.id).toBe('features');
  });

  it('routes a plan question to pricing', () => {
    expect(matchEntry('Which subscription plans are available?')?.id).toBe('pricing');
  });

  it('routes a WhatsApp question to the WhatsApp topic', () => {
    expect(matchEntry('Does AfroDuuka support WhatsApp notifications?')?.id).toBe('whatsapp');
  });

  it('routes a getting-started question correctly even though support also matches', () => {
    expect(matchEntry('How can I get started?')?.id).toBe('how-to-get-started');
  });

  it('returns null when nothing matches', () => {
    expect(matchEntry('what is the weather in Kampala')).toBeNull();
  });
});

describe('questionTerms', () => {
  it('drops stopwords and brand names and keeps meaningful words', () => {
    const terms = questionTerms('Does AfroDuuka support WhatsApp notifications?');

    expect(terms).toContain('whatsapp');
    expect(terms).toContain('notifications');
    expect(terms).toContain('support');
    expect(terms).not.toContain('afroduka');
    expect(terms).not.toContain('does');
  });
});

describe('extractPassages', () => {
  it('keeps only lines containing a question term, and never rewrites them', () => {
    const text = [
      '# Pricing',
      'Our starter plan costs UGX 20,000 per month.',
      'AfroDuuka runs on the cloud.',
      'The growth plan costs UGX 50,000 per month.',
    ].join('\n');

    const passages = extractPassages(text, ['costs']);

    expect(passages).toHaveLength(2);
    expect(passages[0]).toContain('costs');
    // Quoted verbatim, with markdown heading markers stripped.
    expect(passages.join(' ')).toContain('UGX 20,000 per month.');
    expect(passages.join(' ')).not.toContain('#');
  });

  it('caps the number of passages returned', () => {
    const text = Array.from({ length: 10 }, (_, i) => `The plan costs option number ${i} clearly.`).join('\n');

    expect(extractPassages(text, ['plan', 'costs'], 3)).toHaveLength(3);
  });
});

describe('answerQuestion', () => {
  it('answers a verified question locally without any network call', async () => {
    const d = deps();

    const reply = await answerQuestion('What is AfroDuuka?', d);

    expect(reply.text).toMatch(/inventory/i);
    expect(reply.sources).toEqual([]);
    expect(d.fetchPages).not.toHaveBeenCalled();
    expect(d.search).not.toHaveBeenCalled();
  });

  it('answers the features question from curated knowledge', async () => {
    const d = deps();

    const reply = await answerQuestion('What features does it offer?', d);

    expect(reply.text).toMatch(/inventory/i);
    expect(d.fetchPages).not.toHaveBeenCalled();
  });

  it('fetches AfroDuuka pricing live for a plan question', async () => {
    const d = deps({
      fetchPages: vi.fn(async () => [
        {
          url: AFRODUKA_PAGES.pricing,
          title: 'Pricing',
          text: '# Plans\n\nThe starter plan costs UGX 20,000 per month for small shops.',
        },
      ]),
    });

    const reply = await answerQuestion('Which subscription plans are available?', d);

    expect(d.fetchPages).toHaveBeenCalledWith([AFRODUKA_PAGES.pricing]);
    expect(reply.text).toContain('UGX 20,000');
    expect(reply.sources).toEqual([{ title: 'Pricing', url: AFRODUKA_PAGES.pricing }]);
  });

  it('fetches the public home page for a WhatsApp question', async () => {
    const d = deps({
      fetchPages: vi.fn(async () => [
        {
          url: AFRODUKA_PAGES.home,
          title: 'AfroDuuka',
          text: 'You get WhatsApp notifications for low-stock and daily sales summaries.',
        },
      ]),
    });

    const reply = await answerQuestion('Does AfroDuuka support WhatsApp notifications?', d);

    expect(d.fetchPages).toHaveBeenCalledWith([AFRODUKA_PAGES.home]);
    expect(reply.text).toContain('WhatsApp notifications');
  });

  it('falls back to a web search when the fetched page has nothing relevant', async () => {
    const d = deps({
      fetchPages: vi.fn(async () => [
        { url: AFRODUKA_PAGES.pricing, title: 'Pricing', text: 'Content could not be loaded from the server right now.' },
      ]),
      search: vi.fn(async () => [
        { title: 'AfroDuuka pricing', url: 'https://afroduka.com/pricing', snippet: 'Plans from UGX 20,000.' },
      ]),
    });

    const reply = await answerQuestion('Which subscription plans are available?', d);

    expect(d.search).toHaveBeenCalled();
    expect(reply.text).toContain('AfroDuuka pricing');
    expect(reply.sources[0].url).toBe('https://afroduka.com/pricing');
  });

  it('degrades gracefully when the web lookup fails', async () => {
    const d = deps({
      fetchPages: vi.fn(async () => {
        throw new Error('403');
      }),
    });

    const reply = await answerQuestion('Which subscription plans are available?', d);

    expect(reply.text).toMatch(/couldn't reach/i);
    expect(reply.sources).toEqual([]);
  });

  it('does not invent an answer for an unrelated question', async () => {
    const d = deps();

    const reply = await answerQuestion('what is the weather in Kampala', d);

    expect(reply.text).toMatch(/not sure/i);
    expect(d.fetchPages).not.toHaveBeenCalled();
  });
});
