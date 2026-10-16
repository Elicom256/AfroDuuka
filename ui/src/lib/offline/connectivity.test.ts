import { afterEach, describe, expect, it, vi } from 'vitest';
import { ConnectivityMonitor, resolveHealthUrl } from './connectivity';

function jsonResponse(ok: boolean, status = ok ? 200 : 503): Response {
  return { ok, status } as Response;
}

function deferred<T>() {
  let resolve!: (value: T) => void;
  let reject!: (reason?: unknown) => void;
  const promise = new Promise<T>((res, rej) => {
    resolve = res;
    reject = rej;
  });
  return { promise, resolve, reject };
}

/** A vi.fn that is typed as fetch so the monitor accepts it while the mock stays assertable. */
function fetchMock(impl?: (...args: unknown[]) => unknown) {
  const mock = vi.fn(impl);
  return { mock, asFetch: mock as unknown as typeof fetch };
}

afterEach(() => {
  vi.unstubAllEnvs();
});

describe('resolveHealthUrl', () => {
  it('appends /health to the API base and strips trailing slashes', () => {
    vi.stubEnv('VITE_BASE_URL', 'https://api.afroduuka.com/api/');
    expect(resolveHealthUrl()).toBe('https://api.afroduuka.com/api/health');
  });
});

describe('ConnectivityMonitor', () => {
  it('reports reachable when the health probe answers ok', async () => {
    const { mock, asFetch } = fetchMock(() => Promise.resolve(jsonResponse(true)));
    const monitor = new ConnectivityMonitor({ url: '/health', fetchImpl: asFetch });

    const reachable = await monitor.checkNow();

    expect(reachable).toBe(true);
    expect(monitor.getSnapshot()).toMatchObject({ apiReachable: true, checking: false });
    expect(monitor.getSnapshot().lastCheckedAt).not.toBeNull();
    expect(mock).toHaveBeenCalledWith('/health', expect.objectContaining({ method: 'GET' }));
  });

  it('treats a non-ok health response (503) as unreachable', async () => {
    const { asFetch } = fetchMock(() => Promise.resolve(jsonResponse(false)));
    const monitor = new ConnectivityMonitor({ url: '/health', fetchImpl: asFetch });

    expect(await monitor.checkNow()).toBe(false);
    expect(monitor.getSnapshot().apiReachable).toBe(false);
  });

  it('fails closed when the request throws', async () => {
    const { asFetch } = fetchMock(() => Promise.reject(new Error('offline')));
    const monitor = new ConnectivityMonitor({ url: '/health', fetchImpl: asFetch });

    expect(await monitor.checkNow()).toBe(false);
    expect(monitor.getSnapshot().apiReachable).toBe(false);
  });

  it('collapses concurrent probes into a single request', async () => {
    const pending = deferred<Response>();
    const { mock, asFetch } = fetchMock(() => pending.promise);
    const monitor = new ConnectivityMonitor({ url: '/health', fetchImpl: asFetch });

    const first = monitor.checkNow();
    const second = monitor.checkNow();
    expect(second).toBe(first);

    pending.resolve(jsonResponse(true));
    await Promise.all([first, second]);
    expect(mock).toHaveBeenCalledTimes(1);
  });

  it('marks the API unreachable immediately on the browser offline event', async () => {
    const { asFetch } = fetchMock(() => Promise.resolve(jsonResponse(true)));
    const monitor = new ConnectivityMonitor({ url: '/health', fetchImpl: asFetch, intervalMs: 1_000_000 });
    monitor.start();
    await monitor.checkNow();
    expect(monitor.getSnapshot().apiReachable).toBe(true);

    window.dispatchEvent(new Event('offline'));

    expect(monitor.getSnapshot()).toMatchObject({ online: false, apiReachable: false });
    monitor.stop();
  });

  it('re-probes when the browser comes back online', async () => {
    const { mock, asFetch } = fetchMock(() => Promise.resolve(jsonResponse(true)));
    const monitor = new ConnectivityMonitor({ url: '/health', fetchImpl: asFetch, intervalMs: 1_000_000 });
    monitor.start();
    await monitor.checkNow();
    const callsAfterStart = mock.mock.calls.length;

    window.dispatchEvent(new Event('online'));
    await Promise.resolve();

    expect(monitor.getSnapshot().online).toBe(true);
    expect(mock.mock.calls.length).toBeGreaterThan(callsAfterStart);
    monitor.stop();
  });

  it('notifies subscribers when state changes and stops after unsubscribe', async () => {
    const { asFetch } = fetchMock(() => Promise.resolve(jsonResponse(true)));
    const monitor = new ConnectivityMonitor({ url: '/health', fetchImpl: asFetch });
    const listener = vi.fn();
    const unsubscribe = monitor.subscribe(listener);

    await monitor.checkNow();
    expect(listener).toHaveBeenCalled();

    const count = listener.mock.calls.length;
    unsubscribe();
    await monitor.checkNow();
    expect(listener.mock.calls.length).toBe(count);
  });
});
