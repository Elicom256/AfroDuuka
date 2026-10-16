/**
 * Connectivity monitoring.
 *
 * Two signals are tracked separately and deliberately:
 *
 *  - `online` — the browser's own view, from the `online`/`offline` events. It is a
 *    hint, not proof: a device can be "online" while the API is unreachable (dead
 *    wifi upstream, API down, captive portal).
 *  - `apiReachable` — whether the API actually answered a probe. This uses the
 *    existing unauthenticated `GET /api/health` endpoint (it probes DB + cache and
 *    returns 503 when either is unhealthy). Only a healthy `/health` counts as
 *    reachable, because a 503 means writes would fail anyway.
 *
 * No endpoint is invented here (offline.md §4). When `VITE_BASE_URL` is unset the
 * health path resolves to `/health`, and the probe simply fails closed.
 */

export interface ConnectivitySnapshot {
  /** Browser-reported online state. */
  online: boolean;
  /** `true` when the API answered the health probe; `null` before the first probe. */
  apiReachable: boolean | null;
  /** A probe is in flight. */
  checking: boolean;
  /** Timestamp of the last completed probe. */
  lastCheckedAt: number | null;
}

export interface ConnectivityOptions {
  url?: string;
  fetchImpl?: typeof fetch;
  /** Background re-probe interval. Kept coarse on purpose — see offline.md §4. */
  intervalMs?: number;
  timeoutMs?: number;
}

/** The API base URL with any trailing slashes removed (matches chatbot/api.ts). */
function apiBase(): string {
  return (import.meta.env.VITE_BASE_URL ?? '').replace(/\/+$/, '');
}

/** The health endpoint this module probes. Exported for tests and diagnostics. */
export function resolveHealthUrl(): string {
  return `${apiBase()}/health`;
}

export class ConnectivityMonitor {
  private snapshot: ConnectivitySnapshot;
  private readonly listeners = new Set<() => void>();
  private readonly url: string;
  private readonly fetchImpl: typeof fetch;
  private readonly intervalMs: number;
  private readonly timeoutMs: number;
  private refCount = 0;
  private intervalId: ReturnType<typeof setInterval> | null = null;
  private inFlight: Promise<boolean> | null = null;

  constructor(options: ConnectivityOptions = {}) {
    this.url = options.url ?? resolveHealthUrl();
    this.fetchImpl = options.fetchImpl ?? ((...args) => fetch(...args));
    this.intervalMs = options.intervalMs ?? 60_000;
    this.timeoutMs = options.timeoutMs ?? 5_000;
    this.snapshot = {
      online: typeof navigator === 'undefined' ? true : navigator.onLine,
      apiReachable: null,
      checking: false,
      lastCheckedAt: null,
    };
  }

  /** `useSyncExternalStore` subscribe function. Stable reference (arrow property). */
  subscribe = (listener: () => void): (() => void) => {
    this.listeners.add(listener);
    return () => {
      this.listeners.delete(listener);
    };
  };

  /** `useSyncExternalStore` snapshot function. Stable reference (arrow property). */
  getSnapshot = (): ConnectivitySnapshot => this.snapshot;

  /**
   * Begin listening. Reference-counted so several consumers can call it without
   * attaching duplicate listeners or duplicate intervals.
   */
  start = (): void => {
    this.refCount += 1;
    if (this.refCount > 1) return;
    if (typeof window === 'undefined') return;

    window.addEventListener('online', this.handleOnline);
    window.addEventListener('offline', this.handleOffline);
    document.addEventListener('visibilitychange', this.handleVisibility);
    void this.checkNow();
    this.intervalId = setInterval(this.handleTick, this.intervalMs);
  };

  /** Stop listening. Idempotent; the last caller tears everything down. */
  stop = (): void => {
    if (this.refCount === 0) return;
    this.refCount -= 1;
    if (this.refCount > 0) return;
    if (typeof window === 'undefined') return;

    window.removeEventListener('online', this.handleOnline);
    window.removeEventListener('offline', this.handleOffline);
    document.removeEventListener('visibilitychange', this.handleVisibility);
    if (this.intervalId !== null) {
      clearInterval(this.intervalId);
      this.intervalId = null;
    }
  };

  /**
   * Probe the API now. Concurrent callers share one request so a burst of mounts or a
   * reconnect storm cannot fan out into many health calls.
   */
  checkNow = (): Promise<boolean> => {
    if (this.inFlight) return this.inFlight;
    this.patch({ checking: true });
    this.inFlight = this.probe().finally(() => {
      this.inFlight = null;
    });
    return this.inFlight;
  };

  private probe = async (): Promise<boolean> => {
    const controller = typeof AbortController === 'undefined' ? null : new AbortController();
    const timer =
      controller === null ? null : setTimeout(() => controller.abort(), this.timeoutMs);
    try {
      const response = await this.fetchImpl(this.url, {
        method: 'GET',
        headers: { Accept: 'application/json' },
        signal: controller?.signal,
      });
      const reachable = response.ok;
      this.patch({ apiReachable: reachable, checking: false, lastCheckedAt: Date.now() });
      return reachable;
    } catch {
      // DNS failure, offline, CORS, timeout or abort — no healthy answer arrived.
      this.patch({ apiReachable: false, checking: false, lastCheckedAt: Date.now() });
      return false;
    } finally {
      if (timer !== null) clearTimeout(timer);
    }
  };

  private handleOnline = (): void => {
    this.patch({ online: true });
    void this.checkNow();
  };

  private handleOffline = (): void => {
    // The browser is certain there is no network, so mark the API unreachable at once
    // rather than waiting for a probe that cannot succeed.
    this.patch({ online: false, apiReachable: false, checking: false });
  };

  private handleVisibility = (): void => {
    if (document.visibilityState === 'visible') void this.checkNow();
  };

  private handleTick = (): void => {
    // Skip background probes while the tab is hidden or the device is offline — both are
    // cases where the result cannot change anything the user is looking at.
    if (document.visibilityState === 'hidden') return;
    if (!this.snapshot.online) return;
    void this.checkNow();
  };

  private patch(partial: Partial<ConnectivitySnapshot>): void {
    this.snapshot = { ...this.snapshot, ...partial };
    for (const listener of this.listeners) listener();
  }
}

export const connectivity = new ConnectivityMonitor();
