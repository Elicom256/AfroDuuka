import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { FinanceTransactionDetail } from './FinanceTransactionDetail';
import { store } from '@/app/store/app/store';
import { useSetCashFlowDirectionMutation } from '@/app/store/features/business/executive/cashFlowQuery';

/**
 * Two things the detail page does that the table does not.
 *
 * It states the sign of the money in words as well as colour, so an unsigned adjustment
 * has to say its direction is unrecorded rather than assert either way.
 *
 * And it renders whatever the row points at. Which relation is populated decides the
 * label, so a row that references nothing must not present an empty "Sale #" link.
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
  (useSetCashFlowDirectionMutation as unknown as ReturnType<typeof vi.fn>).mockReturnValue([
    setCashFlowDirection,
    { isLoading: false },
  ]);
});

type Detail_ = {
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
  branch?: { name: string } | null;
  created_by?: { firstname?: string | null; lastname?: string | null } | null;
  customer?: { company_name?: string | null; user?: { firstname?: string | null; lastname?: string | null } | null } | null;
  supplier?: { company_name?: string | null; user?: { firstname?: string | null; lastname?: string | null } | null } | null;
  sale?: { id?: number } | null;
  purchase?: { id?: number } | null;
  expense?: { id?: number } | null;
};

const transaction = (overrides: Partial<Detail_> = {}): Detail_ => ({
  id: 1,
  transaction_code: 'CF-S-000001',
  type: 'sale',
  amount: 250000,
  currency: 'UGX',
  category: 'product_sales',
  description: 'Counter sale',
  status: 'completed',
  transaction_date: '2026-10-01',
  ...overrides,
});

const renderDetail = (record: Detail_) =>
  render(
    <Provider store={store}>
      <MemoryRouter>
        <FinanceTransactionDetail transaction={record} />
      </MemoryRouter>
    </Provider>
  );

describe('transaction detail', () => {
  it('says the direction is unrecorded rather than guessing one', () => {
    renderDetail(
      transaction({ type: 'adjustment', transaction_code: 'CF-ADJ-000001', amount: 25000, direction: null })
    );

    expect(screen.getByText('Direction not recorded')).toBeTruthy();
    expect(screen.getByText(/25,000/).textContent).not.toMatch(/^[+-]/);
  });

  it('offers the direction control only on an unsigned adjustment', async () => {
    setCashFlowDirection.mockReturnValue([
      vi.fn().mockResolvedValue({ data: { message: 'ok' } }),
      { isLoading: false },
    ]);
    (useSetCashFlowDirectionMutation as unknown as ReturnType<typeof vi.fn>).mockReturnValue([
      setCashFlowDirection,
      { isLoading: false },
    ]);

    const { unmount } = renderDetail(
      transaction({ type: 'adjustment', transaction_code: 'CF-ADJ-UNSIGNED', direction: null })
    );

    await userEvent.click(screen.getByRole('button', { name: 'Mark CF-ADJ-UNSIGNED as money out' }));

    await waitFor(() => {
      expect(setCashFlowDirection).toHaveBeenCalledWith({ id: 1, direction: 'debit' });
    });

    unmount();

    renderDetail(transaction({ type: 'sale' }));

    expect(screen.queryByRole('button', { name: /Mark .* as money/i })).toBeNull();
  });

  it('names the recorder from their first and last name', () => {
    renderDetail(transaction({ created_by: { firstname: 'Amina', lastname: 'Nabirye' } }));

    expect(screen.getByText('Amina Nabirye')).toBeTruthy();
  });

  it('names a company customer by its company name and a person by theirs', () => {
    const { unmount } = renderDetail(transaction({ customer: { company_name: 'Afro Wholesale Ltd' } }));

    expect(screen.getByText('Afro Wholesale Ltd')).toBeTruthy();

    unmount();

    renderDetail(transaction({ customer: { company_name: null, user: { firstname: 'Grace', lastname: 'Atuhaire' } } }));

    expect(screen.getByText('Grace Atuhaire')).toBeTruthy();
  });

  it('links the source document the row actually points at', () => {
    renderDetail(transaction({ sale: { id: 42 }, purchase: null, expense: null }));

    expect(screen.getByRole('link', { name: /Sale #42/ })).toBeTruthy();
    expect(screen.queryByRole('link', { name: /Purchase/ })).toBeNull();
  });

  it('renders no source link for a row that points at nothing', () => {
    renderDetail(transaction({ type: 'adjustment', direction: 'credit' }));

    expect(screen.queryByRole('link')).toBeNull();
    expect(screen.getByText('None')).toBeTruthy();
  });
});