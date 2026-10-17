import { db } from './db';

/**
 * Device cache of the last successful GET for each list and detail url.
 *
 * Pages keep using RTK Query. `baseQueryWithAuth` writes here after a successful
 * read and, when the network cannot be reached, returns the stored body so the
 * page renders the last data instead of an empty state. A 4xx/5xx is never
 * cached and never replaced with stale data — only a missing response is.
 */

const MAX_READS = 200;

/** Paths that carry credentials or are not user data. */
const NEVER_CACHE_PREFIXES = [
  '/users/login',
  '/users/signup',
  '/users/logout',
  '/users/me',
  '/users/update',
  '/sync/',
  '/verify-email',
];

/**
 * Longer prefixes first so `/returns/sale-returns` is not filed under a shorter
 * sibling. Unlisted GETs still cache, under the first path segment.
 */
const DOMAIN_PREFIXES: Array<{ prefix: string; domain: string }> = [
  { prefix: '/returns/sale-returns', domain: 'sale_returns' },
  { prefix: '/returns/purchase-returns', domain: 'purchase_returns' },
  { prefix: '/purchases', domain: 'purchases' },
  { prefix: '/sales', domain: 'sales' },
  { prefix: '/pos/products', domain: 'products' },
  { prefix: '/pos/customers', domain: 'customers' },
  { prefix: '/products', domain: 'products' },
  { prefix: '/dashboard/customers', domain: 'customers' },
  { prefix: '/dashboard/suppliers', domain: 'suppliers' },
  { prefix: '/dashboard/workers', domain: 'workers' },
  { prefix: '/users/workers', domain: 'workers' },
  { prefix: '/users/todos', domain: 'todos' },
  { prefix: '/sale-orders', domain: 'orders' },
  { prefix: '/quotations', domain: 'quotations' },
  { prefix: '/expenses', domain: 'expenses' },
  { prefix: '/promotions', domain: 'promotions' },
  { prefix: '/coupons', domain: 'coupons' },
  { prefix: '/receipts', domain: 'receipts' },
  { prefix: '/reports', domain: 'reports' },
  { prefix: '/finances', domain: 'finances' },
  { prefix: '/stock-transfers', domain: 'stock_transfers' },
  { prefix: '/users/notifications', domain: 'notifications' },
  { prefix: '/dashboard', domain: 'dashboard' },
];

const SECRET_KEY = /password|token|secret|authorization/i;

export interface ReadRequest {
  method?: string;
  url: string;
  params?: unknown;
}

export interface ServedCache {
  body: unknown;
  cachedAt: number;
}

type CacheNotice = { cachedAt: number; servedAt: number } | null;

let notice: CacheNotice = null;
const noticeListeners = new Set<() => void>();

export function subscribeCacheNotice(listener: () => void): () => void {
  noticeListeners.add(listener);
  return () => {
    noticeListeners.delete(listener);
  };
}

export function getCacheNotice(): CacheNotice {
  return notice;
}

/** Test hook. Production code sets the notice only by serving a cached read. */
export function resetCacheNotice(): void {
  notice = null;
  noticeListeners.forEach((listener) => listener());
}

function noteServed(cachedAt: number): void {
  notice = { cachedAt, servedAt: Date.now() };
  noticeListeners.forEach((listener) => listener());
}

export function normalizePath(url: string): string {
  const path = url.split('?')[0] ?? url;
  if (path.length > 1 && path.endsWith('/')) return path.slice(0, -1);
  return path;
}

export function domainForUrl(url: string): string {
  const path = normalizePath(url);
  const match = DOMAIN_PREFIXES.find((rule) => path === rule.prefix || path.startsWith(`${rule.prefix}/`));
  if (match) return match.domain;
  const segment = path.split('/').filter(Boolean)[0] ?? 'other';
  return segment;
}

export function isCacheableGet(request: ReadRequest): boolean {
  const method = (request.method ?? 'GET').toUpperCase();
  if (method !== 'GET') return false;
  const path = normalizePath(request.url);
  if (!path.startsWith('/')) return false;
  if (path.endsWith('/pdf')) return false;
  return !NEVER_CACHE_PREFIXES.some((prefix) => path === prefix || path.startsWith(prefix));
}

function stableStringify(value: unknown): string {
  if (value === undefined) return '';
  if (value === null || typeof value !== 'object') return JSON.stringify(value) ?? '';
  if (Array.isArray(value)) return `[${value.map((item) => stableStringify(item)).join(',')}]`;
  const record = value as Record<string, unknown>;
  return `{${Object.keys(record)
    .sort()
    .map((key) => `${JSON.stringify(key)}:${stableStringify(record[key])}`)
    .join(',')}}`;
}

export function readCacheKey(request: ReadRequest): string {
  const method = (request.method ?? 'GET').toUpperCase();
  return `${method} ${normalizePath(request.url)} ${stableStringify(request.params)}`;
}

/** True when the envelope itself carries a credential. Nested business data is kept. */
export function containsSecret(body: unknown): boolean {
  if (!body || typeof body !== 'object') return false;
  return Object.keys(body as object).some((key) => SECRET_KEY.test(key));
}

async function evictOldest(): Promise<void> {
  const count = await db.readCache.count();
  if (count <= MAX_READS) return;
  const overflow = await db.readCache.orderBy('cachedAt').limit(count - MAX_READS).primaryKeys();
  await db.readCache.bulkDelete(overflow);
}

/** Store a successful GET. Storage failure is swallowed: a cache miss must not fail the request. */
export async function rememberRead(request: ReadRequest, body: unknown): Promise<void> {
  if (!isCacheableGet(request) || body === undefined || containsSecret(body)) return;
  try {
    await db.readCache.put({
      key: readCacheKey(request),
      domain: domainForUrl(request.url),
      body,
      cachedAt: Date.now(),
    });
    await evictOldest();
  } catch {
    // Quota or a closed database. The live response is already in hand.
  }
}

export async function recallRead(request: ReadRequest): Promise<ServedCache | null> {
  if (!isCacheableGet(request)) return null;
  try {
    const row = await db.readCache.get(readCacheKey(request));
    if (!row) return null;
    noteServed(row.cachedAt);
    return { body: row.body, cachedAt: row.cachedAt };
  } catch {
    return null;
  }
}

/** Drop every cached GET for a domain after a successful write or sync of that domain. */
export async function invalidateReadDomain(domain: string): Promise<void> {
  try {
    await db.readCache.where('domain').equals(domain).delete();
  } catch {
    // A failed invalidation leaves stale rows. The next successful GET overwrites its key.
  }
}

export async function invalidateReadDomains(domains: string[]): Promise<void> {
  for (const domain of domains) {
    await invalidateReadDomain(domain);
  }
}

/** Domain a mutation url belongs to, so a successful write drops that domain's reads. */
export function domainForMutation(url: string): string | null {
  const path = normalizePath(url);
  if (NEVER_CACHE_PREFIXES.some((prefix) => path === prefix || path.startsWith(prefix))) return null;
  return domainForUrl(url);
}
