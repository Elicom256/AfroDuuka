import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { Purchase } from './Purchase';

const receiveMock = vi.fn(() => ({
  unwrap: async () => ({ ok: true }),
}));

vi.mock('@/app/store/features/branch/purchases/purchasesQuery', () => ({
  usePurchaseQuery: () => ({
    data: {
      purchase: {
        id: 1,
        status: 'pending',
        total_amount: 100000,
        note: 'Arriving late',
        created_at: '2026-10-01T12:00:00Z',
        supplier_id: 9,
        purchase_items: [
          {
            id: 11,
            product: { name: 'Galaxy A15' },
            quantity: 20,
            cost_price: 5000,
            selling_price: 7000,
            subtotal: 100000,
          },
        ],
      },
    },
    isLoading: false,
    isFetching: false,
    error: null,
    refetch: vi.fn(),
  }),
  useReceivePurchaseMutation: () => [receiveMock, { isLoading: false }],
}));

vi.mock('@/app/hooks/useCurrency', () => ({
  useCurrency: () => ({ currency: 'UGX' }),
}));

vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
  return {
    ...actual,
    useParams: () => ({ id: '1' }),
    Link: ({ children, ...props }: any) => <a {...props}>{children}</a>,
  };
});

describe('Purchase receive flow', () => {
  it('passes the actual cost and selling values into the receive payload', async () => {
    render(<Purchase />);

    fireEvent.click(screen.getByRole('button', { name: /verify & mark complete/i }));

    await waitFor(() => {
      expect(screen.getByDisplayValue('5000')).toBeInTheDocument();
      expect(screen.getByDisplayValue('7000')).toBeInTheDocument();
    });

    fireEvent.click(screen.getByRole('button', { name: /confirm receipt/i }));

    await waitFor(() => {
      expect(receiveMock).toHaveBeenCalledWith({
        id: 1,
        items: [
          {
            purchase_item_id: 11,
            quantity: 20,
            cost_price: 5000,
            selling_price: 7000,
          },
        ],
        note: 'Arriving late',
      });
    });
  });
});
