/**
 * Curated, public product knowledge for the landing-page chatbot.
 *
 * This file is the chatbot's answer source and is deliberately kept separate from the
 * component so it can be updated on its own. It is derived from the project README —
 * keep the two in sync. When the README is expanded with verified features, plans,
 * pricing and policies, add a matching entry below; never state anything here that the
 * README does not actually claim.
 *
 * An entry whose `answer` is `null` marks a topic the chatbot knows is being asked about
 * but has no verified local information for. When such an entry also names `liveSources`,
 * the responder answers from a genuine TinyFish fetch of those public pages; otherwise it
 * says so honestly and points at where to look rather than inventing an answer.
 */

export type KnowledgeLink = {
  label: string;
  /** A public route or external URL. */
  href: string;
};

export type KnowledgeEntry = {
  id: string;
  /** Short topic name, used for logging and tests. */
  topic: string;
  /** Lowercased keywords and phrases that route a question to this entry. */
  keywords: string[];
  /**
   * The verified answer, or `null` when this topic has no confirmed information yet.
   * Keep it to what the README supports — no invented features, prices or policies.
   */
  answer: string | null;
  /**
   * AfroDuuka pages a live TinyFish fetch should read when `answer` is null. The
   * fetched content is quoted back to the visitor rather than paraphrased, so the
   * chatbot never states anything a page does not. Only public afroduka.com URLs.
   */
  liveSources?: string[];
  /** Optional related links (public pages only). */
  links?: KnowledgeLink[];
};

/** AfroDuuka's public site — the one host the server-side fetch proxy will read. */
export const AFRODUKA_SITE = 'https://afroduka.com';

/** Public pages the chatbot may fetch, keyed by what they cover. */
export const AFRODUKA_PAGES = {
  home: `${AFRODUKA_SITE}/`,
  pricing: `${AFRODUKA_SITE}/pricing`,
} as const;

/** The opening assistant message and the questions shown as chips before the first ask. */
export const WELCOME_MESSAGE =
  'Hello! I am the AfroDuuka assistant. Ask me about AfroDuuka, its features, pricing or how to get started.';

export const SUGGESTED_QUESTIONS = [
  'What is AfroDuuka?',
  'What features does it offer?',
  'Which subscription plans are available?',
  'How can I get started?',
  'Does AfroDuuka support WhatsApp notifications?',
];

export const KNOWLEDGE_ENTRIES: KnowledgeEntry[] = [
  {
    id: 'what-is-afroduka',
    topic: 'What is AfroDuuka',
    keywords: [
      'what is afroduka',
      'afroduka',
      'about afroduka',
      'what does afroduka do',
      'what is this',
      'what is duukaflow',
      'duukaflow',
    ],
    answer:
      'AfroDuuka is smart inventory management software built for African small, medium and large enterprises. ' +
      'It covers the gap in tracking inventory and financial flow in developing countries — especially across Africa — ' +
      'where this has traditionally been done in books, making it hard to run an enterprise efficiently.',
    links: [{ label: 'Get started', href: '/onboarding' }],
  },
  {
    id: 'who-is-it-for',
    topic: 'Who it is for',
    keywords: [
      'who is it for',
      'who is afroduka for',
      'target audience',
      'who should use',
      'who can use',
      'sme',
      'small business',
      'enterprise',
    ],
    answer:
      'AfroDuuka is built for African small and medium enterprises through to large enterprises — growing shops and ' +
      'businesses that want to move from paper-based tracking to a clear, efficient system.',
  },
  {
    id: 'how-to-get-started',
    topic: 'How to get started',
    keywords: [
      'how do i start',
      'how to start',
      'get started',
      'sign up',
      'signup',
      'register',
      'create account',
      'onboard',
      'onboarding',
      'how can i get started',
    ],
    answer:
      'Getting started is a short signup: create your account and set up your business, then you are ready to run your ' +
      'shop. Start with the onboarding flow and you will be walked through it.',
    links: [
      { label: 'Get started', href: '/onboarding' },
      { label: 'View pricing', href: '/pricing' },
    ],
  },
  {
    id: 'features',
    topic: 'Features',
    keywords: [
      'feature',
      'features',
      'what features',
      'what can it do',
      'capabilities',
      'what does it offer',
      'inventory management',
      'stock management',
      'sales',
      'purchases',
      'branch',
      'branches',
      'report',
      'reports',
      'profit',
      'profit and loss',
      'profit-and-loss',
    ],
    answer:
      'AfroDuuka brings your day-to-day operations into one place:\n' +
      '• Inventory and products — product records, quantities, costs, selling prices and reorder thresholds.\n' +
      '• Sales and purchases — record the transactions that move stock.\n' +
      '• Branches and business operations — organise records across locations.\n' +
      '• Financial records and reporting — review cash movements, expenses, inventory value and performance.\n' +
      '• Role-focused dashboards, plus notifications and an assistant as those integrations are enabled.\n' +
      'Specific features depend on your plan and configuration.',
    links: [{ label: 'Get started', href: '/onboarding' }],
  },
  {
    id: 'pricing',
    topic: 'Pricing and plans',
    keywords: [
      'price',
      'pricing',
      'plan',
      'plans',
      'subscription',
      'subscriptions',
      'cost',
      'how much',
      'free',
      'trial',
      'pay',
    ],
    answer: null,
    liveSources: [AFRODUKA_PAGES.pricing],
    links: [{ label: 'View pricing', href: '/pricing' }],
  },
  {
    id: 'whatsapp',
    topic: 'WhatsApp notifications',
    keywords: [
      'whatsapp',
      'whatsapp notification',
      'whatsapp notifications',
      'notifications',
      'notification',
      'alerts',
      'alert',
      'message me',
    ],
    answer: null,
    liveSources: [AFRODUKA_PAGES.home],
    links: [{ label: 'Get started', href: '/onboarding' }],
  },
  {
    id: 'support',
    topic: 'Support',
    keywords: [
      'help',
      'support',
      'contact',
      'contact us',
      'customer service',
      'faq',
      'question',
      'assistance',
      'how do i',
    ],
    answer:
      'For help getting set up, start with the onboarding flow. If you are stuck on something specific, the fastest route ' +
      'is to sign up and reach out from there so we can see exactly where you are.',
    links: [{ label: 'Get started', href: '/onboarding' }],
  },
];
