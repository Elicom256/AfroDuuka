import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { OfflineStatusIndicator } from './OfflineStatusIndicator';
import { useOfflineStatus, EMPTY_OPERATION_COUNTS } from '@/hooks/use-offline-status';

vi.mock('@/hooks/use-offline-status', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/hooks/use-offline-status')>();
  return { ...actual, useOfflineStatus: vi.fn() };
});

const status = (partial: Record<string, unknown> = {}) =>
  ({
    online: true,
    apiReachable: true,
    checking: false,
    lastCheckedAt: null,
    counts: EMPTY_OPERATION_COUNTS,
    pendingCount: 0,
    syncing: false,
    failedCount: 0,
    lastSyncedAt: null,
    allSynced: true,
    ...partial,
  }) as ReturnType<typeof useOfflineStatus>;

beforeEach(() => {
  vi.mocked(useOfflineStatus).mockReset();
});

describe('OfflineStatusIndicator', () => {
  it('reads "Online" when the API is reachable and nothing is queued', () => {
    vi.mocked(useOfflineStatus).mockReturnValue(status());

    render(<OfflineStatusIndicator />);

    expect(screen.getByRole('status')).toHaveTextContent('Online');
  });

  it('reads "Offline" when the browser reports no network', () => {
    vi.mocked(useOfflineStatus).mockReturnValue(status({ online: false, apiReachable: false }));

    render(<OfflineStatusIndicator />);

    expect(screen.getByRole('status')).toHaveTextContent('Offline');
  });

  it('reads "Offline" when the browser says online but the API probe failed', () => {
    vi.mocked(useOfflineStatus).mockReturnValue(status({ online: true, apiReachable: false }));

    render(<OfflineStatusIndicator />);

    expect(screen.getByRole('status')).toHaveTextContent('Offline');
  });

  it('announces the pending and failed counts', () => {
    vi.mocked(useOfflineStatus).mockReturnValue(
      status({ pendingCount: 3, failedCount: 1, allSynced: false }),
    );

    render(<OfflineStatusIndicator />);

    const region = screen.getByRole('status');
    expect(region).toHaveTextContent('3 pending');
    expect(region).toHaveTextContent('1 failed');
    expect(region).toHaveAttribute('aria-live', 'polite');
  });

  it('reports an in-progress sync', () => {
    vi.mocked(useOfflineStatus).mockReturnValue(
      status({ syncing: true, pendingCount: 2, allSynced: false }),
    );

    render(<OfflineStatusIndicator />);

    expect(screen.getByRole('status')).toHaveTextContent('syncing');
  });
});
