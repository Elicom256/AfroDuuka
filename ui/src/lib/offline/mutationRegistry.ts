import type { FetchArgs } from '@reduxjs/toolkit/query';
import type { OperationType } from './types';

/**
 * Which mutations may be queued when the API cannot be reached.
 *
 * The key is `METHOD /path`. A trailing slash is ignored. Paths with an id use
 * `:id`, and a longer static suffix (`/receive`, `/approve`, …) is matched
 * before the bare id form.
 *
 * Only types the sync contract can replay are listed. A mutation that is absent
 * keeps today's error toast — queueing it would invent a success the server
 * will never confirm. POS checkout is absent on purpose: a sale is written
 * through `submitSale`, which also stores the local sale, its lines and its
 * stock adjustments.
 */
export interface MutationRoute {
  type: OperationType;
  /** What the handler should do. Omitted for the original flat create payloads. */
  action?: 'create' | 'update' | 'delete' | 'receive' | 'approve' | 'send' | 'accept' | 'cancel';
  /**
   * When true, the outbox payload is the request body itself (the shape the
   * existing todo and customer handlers already validate). Otherwise the payload
   * is `{ action, id, body }`.
   */
  flat?: boolean;
}

interface RoutePattern extends MutationRoute {
  method: string;
  /** Path with `:id` where a single segment is the resource id. */
  pattern: string;
}

const ROUTES: RoutePattern[] = [
  { method: 'POST', pattern: '/users/todos', type: 'todo', action: 'create', flat: true },
  { method: 'POST', pattern: '/dashboard/customers', type: 'customer', action: 'create', flat: true },
];

export interface MatchedMutation {
  route: MutationRoute;
  id: string | null;
}

function normalize(url: string): string {
  const path = url.split('?')[0] ?? url;
  if (path.length > 1 && path.endsWith('/')) return path.slice(0, -1);
  return path;
}

function matchPattern(pattern: string, path: string): { id: string | null } | null {
  const patternParts = pattern.split('/').filter(Boolean);
  const pathParts = path.split('/').filter(Boolean);
  if (patternParts.length !== pathParts.length) return null;

  let id: string | null = null;
  for (let index = 0; index < patternParts.length; index += 1) {
    const expected = patternParts[index];
    const actual = pathParts[index];
    if (expected === ':id') {
      id = actual;
      continue;
    }
    if (expected !== actual) return null;
  }
  return { id };
}

export function matchMutation(method: string, url: string): MatchedMutation | null {
  const path = normalize(url);
  const verb = method.toUpperCase();
  for (const route of ROUTES) {
    if (route.method !== verb) continue;
    const matched = matchPattern(route.pattern, path);
    if (!matched) continue;
    return {
      id: matched.id,
      route: { type: route.type, action: route.action, flat: route.flat },
    };
  }
  return null;
}

export function requestUrl(args: string | FetchArgs): string {
  return typeof args === 'string' ? args : (args.url ?? '');
}

export function requestMethod(args: string | FetchArgs): string {
  if (typeof args === 'string') return 'GET';
  return (args.method ?? 'GET').toUpperCase();
}

export function requestBody(args: string | FetchArgs): unknown {
  if (typeof args === 'string') return null;
  return args.body ?? null;
}

/** Routes the seam knows about. Tests use this to prove a 4xx path is simply not queued. */
export function registeredMutationCount(): number {
  return ROUTES.length;
}
