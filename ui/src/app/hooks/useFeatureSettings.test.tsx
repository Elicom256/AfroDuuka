import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';

import { useFeatureSettings } from './useFeatureSettings';
import { useGetReportsSettingsQuery } from '@/app/store/features/business/settings/reports';
import { useGetCustomerSettingsQuery } from '@/app/store/features/business/settings/customer';
import { useGetSupplierSettingsQuery } from '@/app/store/features/business/settings/supplier';
import { useGetPromotionsSettingsQuery } from '@/app/store/features/business/settings/promotions';
import { useGetAttendanceSettingsQuery } from '@/app/store/features/business/settings/attendance';

vi.mock('@/app/store/features/business/settings/reports', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/app/store/features/business/settings/reports')>();
  return { ...actual, useGetReportsSettingsQuery: vi.fn() };
});
vi.mock('@/app/store/features/business/settings/customer', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/app/store/features/business/settings/customer')>();
  return { ...actual, useGetCustomerSettingsQuery: vi.fn() };
});
vi.mock('@/app/store/features/business/settings/supplier', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/app/store/features/business/settings/supplier')>();
  return { ...actual, useGetSupplierSettingsQuery: vi.fn() };
});
vi.mock('@/app/store/features/business/settings/promotions', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/app/store/features/business/settings/promotions')>();
  return { ...actual, useGetPromotionsSettingsQuery: vi.fn() };
});
vi.mock('@/app/store/features/business/settings/attendance', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/app/store/features/business/settings/attendance')>();
  return { ...actual, useGetAttendanceSettingsQuery: vi.fn() };
});

const stubs = (payload: unknown) => {
  const result = { data: payload, isLoading: false };

  vi.mocked(useGetReportsSettingsQuery).mockReturnValue(
    result as unknown as ReturnType<typeof useGetReportsSettingsQuery>
  );
  vi.mocked(useGetCustomerSettingsQuery).mockReturnValue(
    result as unknown as ReturnType<typeof useGetCustomerSettingsQuery>
  );
  vi.mocked(useGetSupplierSettingsQuery).mockReturnValue(
    result as unknown as ReturnType<typeof useGetSupplierSettingsQuery>
  );
  vi.mocked(useGetPromotionsSettingsQuery).mockReturnValue(
    result as unknown as ReturnType<typeof useGetPromotionsSettingsQuery>
  );
  vi.mocked(useGetAttendanceSettingsQuery).mockReturnValue(
    result as unknown as ReturnType<typeof useGetAttendanceSettingsQuery>
  );
};

const Probe = () => {
  const features = useFeatureSettings();

  return (
    <div>
      <span data-testid='suppliers'>{String(features.suppliers)}</span>
      <span data-testid='customers'>{String(features.customers)}</span>
    </div>
  );
};

/**
 * Every settings endpoint answers `{ settings, message }`, but isEnabled() read
 * `data.data` and then `data.status` — neither of which exists — so every feature it
 * gated read false forever. Suppliers and Customers (enabled by default since the
 * item-14 fix) never appeared in the People sidebar no matter what the row said, and
 * the same hook hid Reports, Promotions and Attendance with them.
 */
describe('useFeatureSettings', () => {
  it('reads an enabled setting from the shape the API actually returns', () => {
    stubs({ settings: { status: 'enabled' }, message: 'Supplier settings' });

    render(<Probe />);

    expect(screen.getByTestId('suppliers').textContent).toBe('true');
    expect(screen.getByTestId('customers').textContent).toBe('true');
  });

  it('keeps a disabled feature out of the sidebar', () => {
    stubs({ settings: { status: 'disabled' }, message: 'Supplier settings' });

    render(<Probe />);

    expect(screen.getByTestId('suppliers').textContent).toBe('false');
  });

  it('still understands a bare payload and an array of settings', () => {
    stubs({ status: 'enabled' });
    const bare = render(<Probe />);
    expect(bare.getByTestId('suppliers').textContent).toBe('true');
    bare.unmount();

    stubs([{ status: 'enabled' }]);
    const list = render(<Probe />);
    expect(list.getByTestId('suppliers').textContent).toBe('true');
  });

  it('is off while loading rather than flickering enabled', () => {
    stubs(undefined);

    render(<Probe />);

    expect(screen.getByTestId('suppliers').textContent).toBe('false');
  });
});
