import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

import { ReceiptDetail } from '../pages/dashboards/executive/components/receipts/Receipt';
import { ReceiptView } from '../pages/dashboards/executive/components/receipts/ReceiptView';
import { PosReceiptModal } from '../pages/dashboards/shared/pos/PosReceiptModal';
import { store } from '../store/app/store';
import { authQuery } from '../store/features/auth/authQuery';
import { receiptsQuery } from '../store/features/branch/receipts/receiptsQuery';
import { setToken } from '@/lib/session';

/**
 * One receipt, one design.
 *
 * The bug: the POS receipt modal was written separately from the receipt page and had
 * drifted — a different header, a different item list, and a different set of totals.
 * The same sale therefore read two ways depending on where it was sold, and the till
 * receipt omitted fields the receipt page showed (receipt number, cashier, payment
 * method, amount paid, change given, discount).
 *
 * The fix extracts the receipt page's document into ReceiptView and renders it in both
 * places. These tests pin that by construction:
 *   1. the POS modal renders ReceiptView, not its own markup;
 *   2. ReceiptDetail renders the same component, so the two cannot drift apart again;
 *   3. every field of the design survives, including the ones POS used to omit.
 *
 * Asserting on the rendered text rather than on class names keeps this about what a
 * customer actually sees.
 */

const requestUrl = (input: RequestInfo | URL): string => {
  if (typeof input === 'string') return input;
  if (typeof URL !== 'undefined' && input instanceof URL) return input.toString();
  if (typeof Request !== 'undefined' && input instanceof Request) return input.url;
  return String(input);
};

const RECEIPT = {
  id: 2,
  receipt_number: 'RCP-0002',
  status: 'completed',
  created_at: '2026-10-05T09:30:00Z',
  subtotal: '400000.00',
  discount: '20000.00',
  tax: '18000.00',
  total: '398000.00',
  amount_paid: '500000.00',
  change_given: '102000.00',
  payment_method: 'mobile_money',
  notes: 'Delivered to the counter',
  user: { firstname: 'Ada', lastname: 'Lovelace' },
  customer: null,
  items: [
    {
      id: 11,
      product_name: 'Phone',
      sku: 'PH-001',
      quantity: 2,
      unit_price: '200000.00',
      discount: '20000.00',
      line_total: '380000.00',
    },
  ],
};

const stubFetch = () => {
  vi.stubGlobal(
    'fetch',
    vi.fn(async (input: RequestInfo | URL) => {
      const url = requestUrl(input);
      const json = (body: unknown) =>
        new Response(JSON.stringify(body), {
          status: 200,
          headers: { 'Content-Type': 'application/json' },
        });

      if (url.includes('/receipts/2') && !url.includes('/pdf')) return json({ receipt: RECEIPT });
      if (url.includes('/users/me')) {
        return json({
          data: {
            id: 1,
            name: 'Ada',
            role: { name: 'Executive' },
            business: { id: 1, name: 'Test Business' },
            business_branch_id: 1,
          },
        });
      }

      return json({ data: {} });
    })
  );
};

beforeEach(async () => {
  localStorage.clear();
  setToken('a.valid.token.value');
  store.dispatch(authQuery.util.resetApiState());
  // The receipts slice has to be reset too. RTK Query kept the receipt fetched by an
  // earlier test, so a later test was served from cache and issued no request at all —
  // which is exactly the request an assertion about fetching needs to observe.
  store.dispatch(receiptsQuery.util.resetApiState());
  stubFetch();
});

/**
 * Rendered through a real <Route path='/dashboard/receipts/:id'> rather than as a bare
 * component. ReceiptDetail reads the id with useParams(), so without a matching route it
 * received undefined, skipped its query and rendered "Receipt not found" — which made
 * the missing-receipt test below pass for entirely the wrong reason.
 */
const renderAt = (path: string) =>
  render(
    <Provider store={store}>
      <MemoryRouter initialEntries={[path]}>
        <Routes>
          <Route path='/dashboard/receipts/:id' element={<ReceiptDetail />} />
        </Routes>
      </MemoryRouter>
    </Provider>
  );

/** Every label the shared design is made of. If POS ever renders its own markup again,
 *  or the shared design loses a field, one of these goes missing. */
const DESIGN_LABELS = [
  'Receipt RCP-0002',
  'completed',
  'Cashier',
  'Ada Lovelace',
  'Customer',
  'Walk-in Customer',
  'Payment Method',
  'mobile money', // capitalised by the `capitalize` class, which jsdom does not apply
  'Receipt Number',
  'Products',
  'Phone',
  'PH-001',
  'Subtotal',
  'Discount',
  'Tax',
  'Grand Total',
  'Amount Paid',
  'Change Given',
  'Notes',
  'Delivered to the counter',
];

describe('the receipt design is identical wherever a receipt appears', () => {
  it('the receipt page renders every field of the design', async () => {
    renderAt('/dashboard/receipts/2');

    await waitFor(() => {
      expect(screen.queryAllByText('Grand Total').length).toBeGreaterThan(0);
    });

    for (const label of DESIGN_LABELS) {
      expect(screen.queryAllByText(label).length, `missing from the receipt page: ${label}`).toBeGreaterThan(0);
    }
  });

  it('the shared ReceiptView renders the design on its own', () => {
    // Stands in for every other surface, POS included: it is the component they all
    // render, so this is the assertion that pins their output.
    render(
      <Provider store={store}>
        <MemoryRouter>
          <ReceiptView receipt={RECEIPT} />
        </MemoryRouter>
      </Provider>
    );

    for (const label of DESIGN_LABELS) {
      expect(screen.queryAllByText(label).length, `missing from ReceiptView: ${label}`).toBeGreaterThan(0);
    }
  });

  it('receives the same payload from the receipts endpoint the POS modal uses', async () => {
    // The POS modal no longer assembles a receipt from the sale it just created,
    // because that payload carries receipt.items but not receipt.user or
    // receipt.customer, both of which the design renders.
    renderAt('/dashboard/receipts/2');

    await waitFor(() => {
      expect(screen.queryAllByText('Ada Lovelace').length).toBeGreaterThan(0);
    });

    const calls = (globalThis.fetch as ReturnType<typeof vi.fn>).mock.calls.map(([input]) => requestUrl(input));
    expect(calls.some((url) => url.includes('/receipts/2'))).toBe(true);
  });

  it('shows a not-found message instead of a zeroed document when the receipt is gone', async () => {
    // A missing receipt must never render an empty document, which would read as a real
    // sale worth nothing. ReceiptView is only reached once a receipt exists, so the
    // guard being pinned here is the guard both surfaces rely on.
    vi.stubGlobal(
      'fetch',
      vi.fn(async (input: RequestInfo | URL) => {
        const url = requestUrl(input);
        const json = (body: unknown) =>
          new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } });
        if (url.includes('/receipts/')) return json({});
        if (url.includes('/users/me')) {
          return json({
            data: {
              id: 1,
              name: 'Ada',
              role: { name: 'Executive' },
              business: { id: 1, name: 'Test Business' },
              business_branch_id: 1,
            },
          });
        }
        return json({ data: {} });
      })
    );

    renderAt('/dashboard/receipts/999');

    await waitFor(() => {
      expect(screen.queryAllByText('Receipt not found').length).toBeGreaterThan(0);
    });

    expect(screen.queryAllByText('Grand Total').length).toBe(0);
  });
});

/**
 * The assertion that actually holds POS to the shared design.
 *
 * The first three tests pin the receipt page and the shared component, and all of them
 * passed while the POS modal still had its own markup — because nothing rendered POS.
 * This one renders the till's receipt surface directly, so re-introducing bespoke markup
 * there loses the whole design at once.
 */
describe('the till receipt uses the shared design', () => {
  const renderTillReceipt = (receiptId: number | null) =>
    render(
      <Provider store={store}>
        <MemoryRouter>
          <PosReceiptModal receiptId={receiptId} onClose={() => {}} />
        </MemoryRouter>
      </Provider>
    );

  it('shows every field of the receipt page design after a sale', async () => {
    renderTillReceipt(RECEIPT.id);

    await waitFor(() => {
      expect(screen.queryAllByText('Grand Total').length).toBeGreaterThan(0);
    });

    for (const label of DESIGN_LABELS) {
      expect(screen.queryAllByText(label).length, `missing from the till receipt: ${label}`).toBeGreaterThan(0);
    }
  });

  it('does not fall back to the old bare subtotal-and-total layout', async () => {
    // Those two labels are what the POS receipt used to show, and all it showed. Their
    // absence of exclusivity is the point: the design has to carry the fields it lacked.
    renderTillReceipt(RECEIPT.id);

    await waitFor(() => {
      expect(screen.queryAllByText('Grand Total').length).toBeGreaterThan(0);
    });

    // The old layout had no products table and no receipt number.
    expect(screen.queryAllByText('Receipt Number').length).toBeGreaterThan(0);
    expect(screen.queryAllByText('Cashier').length).toBeGreaterThan(0);
    expect(screen.queryAllByText('Change Given').length).toBeGreaterThan(0);
    expect(screen.queryAllByText('Products').length).toBeGreaterThan(0);
  });

  it('says so plainly when the receipt cannot be loaded, rather than showing an empty one', async () => {
    renderTillReceipt(null);

    await waitFor(() => {
      expect(screen.queryAllByText(/could not be loaded/i).length).toBeGreaterThan(0);
    });

    expect(screen.queryAllByText('Grand Total').length).toBe(0);
  });
});
