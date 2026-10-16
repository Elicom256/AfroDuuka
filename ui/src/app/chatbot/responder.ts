/**
 * Turns a visitor's question into a reply.
 *
 * Two paths, in this order, and the order is the point:
 *
 *  1. Curated knowledge. If a topic's `answer` is verified, it is returned as-is and no
 *     network call is made. This is the common case and the task's "avoid calling
 *     TinyFish unnecessarily" rule.
 *  2. Live retrieval. For the few topics with no verified local answer (`pricing`,
 *     `whatsapp`), the responder asks the server-side proxy to fetch AfroDuuka's own
 *     public pages. Passages that match the question are quoted back verbatim — never
 *     paraphrased — with a source link, so the chatbot cannot state anything a page does
 *     not. A web search is the second fallback; a plain, honest message is the last.
 *
 * Nothing here reads business data and nothing is authenticated, so it is safe to run
 * for any visitor.
 */
import {
  KNOWLEDGE_ENTRIES,
  WELCOME_MESSAGE,
  SUGGESTED_QUESTIONS,
  type KnowledgeEntry,
  type KnowledgeLink,
} from './knowledge';
import type { FetchedPage, SearchHit } from './api';

export { WELCOME_MESSAGE, SUGGESTED_QUESTIONS };

export type ChatSource = { title: string; url: string };

export type ChatbotReply = {
  text: string;
  links: KnowledgeLink[];
  sources: ChatSource[];
};

export type ChatbotDeps = {
  fetchPages: (urls: string[]) => Promise<FetchedPage[]>;
  search: (query: string) => Promise<SearchHit[]>;
};

/** Words too common to distinguish one page passage from another. */
const STOPWORDS = new Set([
  'what', 'which', 'does', 'do', 'the', 'for', 'and', 'are', 'can', 'how', 'about',
  'with', 'your', 'you', 'that', 'this', 'from', 'have', 'has', 'was', 'were', 'will',
  'would', 'there', 'their', 'they', 'them', 'then', 'than', 'into', 'over', 'more',
  'some', 'any', 'all', 'also', 'just', 'get', 'got', 'use', 'using', 'want', 'need',
  'afroduka', 'afroduuka', 'duuka',
]);

const normalize = (value: string): string =>
  value
    .toLowerCase()
    // The product is branded "AfroDuuka" but its public domain is afroduka.com, so the
    // two spellings are the same topic and must route identically.
    .replace(/afroduuka/g, 'afroduka')
    .replace(/[^a-z0-9\s]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

/**
 * The knowledge entry a question is about, or null when nothing matches.
 *
 * Scored by the total length of every matched keyword, so a specific phrase ("what
 * features") outranks an incidental one ("features"), and a topic that matches several
 * keywords wins over a topic that matches one. Ties keep document order.
 */
export function matchEntry(question: string): KnowledgeEntry | null {
  const text = normalize(question);
  if (!text) return null;

  let best: KnowledgeEntry | null = null;
  let bestScore = 0;

  for (const entry of KNOWLEDGE_ENTRIES) {
    let score = 0;
    for (const keyword of entry.keywords) {
      const needle = normalize(keyword);
      if (needle && text.includes(needle)) {
        score += needle.length;
      }
    }

    if (score > bestScore) {
      best = entry;
      bestScore = score;
    }
  }

  return best;
}

/** Distinct meaningful words in a question, used to score page passages. */
export function questionTerms(question: string): string[] {
  const words = normalize(question)
    .split(' ')
    .filter((word) => word.length >= 4 && !STOPWORDS.has(word));

  return [...new Set(words)];
}

/** Crude singularisation, enough to match "plans" against "plan" on a fetched page. */
const stem = (word: string): string => (word.length > 3 ? word.replace(/s$/, '') : word);

const cleanLine = (line: string): string =>
  line
    .replace(/!\[[^\]]*\]\([^)]*\)/g, '')
    .replace(/\[([^\]]+)\]\([^)]*\)/g, '$1')
    .replace(/^[#>*\-\s]+/, '')
    .replace(/[*_`]/g, '')
    .trim();

/**
 * The lines of a fetched page that mention the question's terms, best first.
 *
 * Purely extractive: every returned string is an exact line of the page, so the answer
 * cannot hallucinate. Lines shorter than a phrase or longer than a paragraph are noise
 * and are dropped.
 */
export function extractPassages(text: string, terms: string[], max = 4): string[] {
  const termStems = new Set(terms.map((term) => stem(normalize(term))));
  const seen = new Set<string>();
  const scored: { line: string; score: number }[] = [];

  for (const raw of text.split(/\r?\n/)) {
    const line = cleanLine(raw);
    if (line.length < 24 || line.length > 400 || seen.has(line)) continue;

    const tokens = normalize(line).split(' ').filter(Boolean);
    const score = tokens.reduce((total, token) => total + (termStems.has(stem(token)) ? 1 : 0), 0);
    if (score === 0) continue;

    seen.add(line);
    scored.push({ line, score });
  }

  return scored
    .sort((a, b) => b.score - a.score)
    .slice(0, max)
    .map((item) => item.line);
}

const bulletList = (items: string[]): string => items.map((item) => `• ${item}`).join('\n');

function localReply(entry: KnowledgeEntry): ChatbotReply {
  return { text: entry.answer ?? '', links: entry.links ?? [], sources: [] };
}

async function liveReply(
  question: string,
  entry: KnowledgeEntry,
  deps: ChatbotDeps,
): Promise<ChatbotReply> {
  const links = entry.links ?? [];
  const terms = questionTerms(question);

  try {
    const pages = await deps.fetchPages(entry.liveSources ?? []);

    for (const page of pages) {
      const passages = extractPassages(page.text, terms);
      if (passages.length > 0) {
        return {
          text: `Here is what AfroDuuka's website says:\n\n${bulletList(passages)}`,
          links,
          sources: [{ title: page.title || 'AfroDuuka', url: page.url }],
        };
      }
    }

    // The pages rendered but did not answer the question. A web search is the second,
    // narrower try before giving up.
    const hits = await deps.search(`${question} AfroDuuka`);
    if (hits.length > 0) {
      const top = hits.slice(0, 3);
      return {
        text: `I could not find that on AfroDuuka's own pages, but here is what the web shows:\n\n${bulletList(
          top.map((hit) => (hit.snippet ? `${hit.title} — ${hit.snippet}` : hit.title)),
        )}`,
        links,
        sources: top.map((hit) => ({ title: hit.title || 'Source', url: hit.url })),
      };
    }

    return {
      text: "I couldn't find that on AfroDuuka's website just now. Try the pricing page, or start onboarding and the team can help from there.",
      links,
      sources: [],
    };
  } catch {
    return {
      text: "I couldn't reach AfroDuuka's website to check that. Please try again in a moment.",
      links,
      sources: [],
    };
  }
}

/**
 * Answer a question, using the network only when the curated knowledge cannot.
 */
export async function answerQuestion(question: string, deps: ChatbotDeps): Promise<ChatbotReply> {
  const entry = matchEntry(question);

  if (entry && entry.answer) {
    return localReply(entry);
  }

  if (entry && entry.liveSources && entry.liveSources.length > 0) {
    return liveReply(question, entry, deps);
  }

  if (entry) {
    return {
      text: "I don't have a verified answer for that yet. The onboarding flow is the quickest way to reach the team and get a definite answer.",
      links: entry.links ?? [{ label: 'Get started', href: '/onboarding' }],
      sources: [],
    };
  }

  return {
    text: "I'm not sure about that one. I can help with what AfroDuuka is, the features it offers, pricing and plans, and how to get started.",
    links: [
      { label: 'Get started', href: '/onboarding' },
      { label: 'View pricing', href: '/pricing' },
    ],
    sources: [],
  };
}
