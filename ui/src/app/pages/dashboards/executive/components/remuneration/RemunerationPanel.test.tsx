import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';

import { RemunerationPanel } from './RemunerationPanel';
import { useGetWorkersInfoQuery } from '@/app/store/features/business/workers/workersQuery';

vi.mock('@/app/store/features/business/workers/workersQuery', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/app/store/features/business/workers/workersQuery')>();
  return { ...actual, useGetWorkersInfoQuery: vi.fn() };
});

vi.mock('@/app/store/features/business/executive/employeeRemunerationQuery', async (importOriginal) => {
  const actual =
    await importOriginal<typeof import('@/app/store/features/business/executive/employeeRemunerationQuery')>();
  return {
    ...actual,
    useStoreExecutiveEmployeeRemunerationMutation: vi.fn(() => [vi.fn(), { isLoading: false }]),
  };
});

const mockWorkers = (result: unknown) =>
  vi.mocked(useGetWorkersInfoQuery).mockReturnValue(result as ReturnType<typeof useGetWorkersInfoQuery>);

const renderPanel = (payroll: any[]) =>
  render(<RemunerationPanel payroll={payroll} totalPaid={0} employeeCount={0} pending={0} />);

/**
 * The date-format crash class from now.md, on the employee-remuneration CRUD page.
 * `format(new Date(payment_date))` throws a RangeError for a missing or malformed
 * date; because it ran inside a table row, the throw unmounted the page and it went
 * blank instead of showing the row with a placeholder. Every payroll row must render
 * for any date value the server may send back.
 */
describe('RemunerationPanel date guards', () => {
  beforeEach(() => {
    vi.mocked(useGetWorkersInfoQuery).mockReset();
    mockWorkers({ data: { workers: [] }, isLoading: false });
  });

  it('keeps the row when payment_date is missing', () => {
    renderPanel([
      {
        id: 7,
        amount: 500,
        status: 'pending',
        payment_date: undefined,
        worker: { user: { firstname: 'No', lastname: 'Date' } },
      },
    ]);

    expect(screen.getByText('No Date')).toBeDefined();
    expect(screen.getByText('-')).toBeDefined();
  });

  it('keeps the row when payment_date is malformed', () => {
    renderPanel([
      {
        id: 8,
        amount: 700,
        status: 'paid',
        payment_date: 'not-a-date',
        worker: { user: { firstname: 'Bad', lastname: 'Date' } },
      },
    ]);

    expect(screen.getByText('Bad Date')).toBeDefined();
    expect(screen.getByText('-')).toBeDefined();
  });

  it('keeps the row when a valid payment_date is present', () => {
    renderPanel([
      {
        id: 9,
        amount: 1000,
        status: 'paid',
        payment_date: '2026-01-02',
        worker: { user: { firstname: 'Amina', lastname: 'K' } },
      },
    ]);

    expect(screen.getByText('Amina K')).toBeDefined();
  });
});
