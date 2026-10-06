import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { FinanceTransactionTable } from './FinanceTransactionTable';
import { store } from '@/app/store/app/store';
import { useSetCashFlowDirectionMutation } from '@/app/store/features/business/executive/cashFlowQuery';

/**
 * An adjustment written before a direction was required is the one ledger row the server
 * cannot sign: cashEffect() returns 0 for it, so it is excluded from the cash balance, and
 * the executive dashboard warns about it and tells the user to fix it here.
 *
 * So this table has three jobs that are easy to break independently:
 *
 *   - the row offers a way to record the answer, or the warning points nowhere;
 *   - only an unsigned adjustment offers it, because the server refuses to clear a
 *     direction that is already recorded;
 *   - an unsigned adjustment is not drawn as an inflow. Keying the sign off `type` alone
 *     painted every adjustment with a green `+`, on an amount that could equally have left
 *     the till — a guess presented as fact on the row a user is being asked to judge.
 */

vi.mock('@/app/store/features/business/executive/cashFlowQuery', async (importOriginal) => {
  const actual = await importOriginal<
    typeof import('@/app/store/features/business/executive/cashFlowQuery')
  >();

  return {
    ...actual,
    useSetCashFlowDirectionMutation: vi.fn(),
  };
});

vi.mock('sonner', () => ({
  toast: { success: vi.fn(), error: vi.fn() },
}));

const setCashFlowDirection = vi.fn();

beforeEach(() => {
  vi.clearAllMocks();
  setCashFlowDirection.mockReturnValue([setCashFlowDirection, { isLoading: false }]);
  (useSetCashFlowDirectionMutation as unknown as ReturnType<typeof vi.fn>).mockReturnValue([
    setCashFlowDirection,
    { isLoading: false },
  ]);
});

type Record_ = {
  id: number;
  transaction_code: string;
  type: string;
  direction?: 'credit' | 'debit' | null;
  amount: number;
  currency: string;
  category: string;
  description: string;
  status: string;
  transaction_date: string;
};

const record = (overrides: Partial<Record_> = {}): Record_ => ({
  id: 1,
  transaction_code: 'CF-ADJ-000001',
  type: 'adjustment',
  amount: 25000,
  currency: 'UGX',
  category: '',
  description: 'Counting correction',
  status: 'completed',
  transaction_date: '2026-10-01',
  ...overrides,
});

const renderTable = (records: Record_[]) =>
  render(
    <Provider store={store}>
      <MemoryRouter>
        <FinanceTransactionTable records={records} currentPage={1} totalPages={1} onPageChange={vi.fn()} />
      </MemoryRouter>
    </Provider>
  );

describe('unsigned adjustments in the transactions table', () => {
  it('offers to record a direction for an adjustment that has none', async () => {
    renderTable([record({ id: 7, transaction_code: 'CF-ADJ-UNSIGNED' })]);

    expect(screen.getByRole('button', { name: 'Mark CF-ADJ-UNSIGNED as money in' })).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Mark CF-ADJ-UNSIGNED as money out' })).toBeTruthy();
  });

  it('sends the chosen direction for that row', async () => {
    setCashFlowDirection.mockReturnValue([
      vi.fn().mockResolvedValue({ data: { message: 'ok' } }),
      { isLoading: false },
    ]);
    (useSetCashFlowDirectionMutation as unknown as ReturnType<typeof vi.fn>).mockReturnValue([
      setCashFlowDirection,
      { isLoading: false },
    ]);

    renderTable([record({ id: 7, transaction_code: 'CF-ADJ-UNSIGNED' })]);

    await userEvent.click(screen.getByRole('button', { name: 'Mark CF-ADJ-UNSIGNED as money out' }));

    await waitFor(() => {
      expect(setCashFlowDirection).toHaveBeenCalledWith({ id: 7, direction: 'debit' });
    });
  });

  it('offers nothing for an adjustment that already records a direction', () => {
    renderTable([record({ id: 8, direction: 'credit' })]);

    expect(screen.queryByRole('button', { name: /Mark CF-ADJ-000001 as money/i })).toBeNull();
    expect(screen.getByText('Money in')).toBeTruthy();
  });

  it('offers nothing on a derived type, which takes its sign from itself', () => {
    renderTable([record({ id: 9, type: 'sale', direction: null })]);

    expect(screen.queryByRole('button', { name: /Mark .* as money/i })).toBeNull();
  });

  /**
   * The display bug that sits directly on this invariant. `isOutflow` used to read `type`
   * only, and `adjustment` is not in the outflow list, so a legacy adjustment rendered
   * with a green `+` on an amount the server had not signed at all.
   */
  it('does not draw an unsigned adjustment as an inflow', () => {
    renderTable([record({ amount: 25000 })]);

    const amount = screen.getByText(/25,000/);

    expect(amount.textContent).not.toMatch(/^\+/);
    expect(amount.className).toContain('text-muted-foreground');
  });

  it('draws a credit adjustment as an inflow and a debit one as an outflow', () => {
    const { unmount } = renderTable([record({ id: 10, direction: 'credit', amount: 1000 })]);

    expect(screen.getByText(/1,000/).textContent).toMatch(/^\+/);

    unmount();

    renderTable([record({ id: 11, direction: 'debit', amount: 1000 })]);

    expect(screen.getByText(/1,000/).textContent).toMatch(/^-/);
  });

  it('keeps the derived types signed by type, unchanged', () => {
    const { unmount } = renderTable([record({ id: 12, type: 'expense', amount: 500 })]);

    expect(screen.getByText(/500/).textContent).toMatch(/^-/);

    unmount();

    renderTable([record({ id: 13, type: 'sale', amount: 500 })]);

    expect(screen.getByText(/500/).textContent).toMatch(/^\+/);
  });
});
