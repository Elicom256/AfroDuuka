import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { CreateProductAudit } from './CreateProductAudit';
import { store } from '@/app/store/app/store';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';

/**
 * A product audit is a physical stock count: the user ticks what is on the shelf
 * against what the system says. The product list is the whole form -- with no options
 * in the select there is nothing to count, the submit guard rejects the form, and
 * auditing is impossible.
 *
 * It rendered zero options because the dialog read `products.data` while
 * ProductController::index() answers `{ message, products }` with products a plain
 * collection. The `?? []` swallowed the undefined, so the failure was silent: no error,
 * just an empty dropdown.
 *
 * The fixture below is the endpoint's real shape. A paginator-shaped `{ data: [...] }`
 * would pass against the old code and prove nothing.
 */

vi.mock('@/app/store/features/branch/products/branchProductsQuery', async (importOriginal) => {
  const actual = await importOriginal<
    typeof import('@/app/store/features/branch/products/branchProductsQuery')
  >();

  return { ...actual, useProductsQuery: vi.fn() };
});

vi.mock('@/app/store/features/audit/productAuditQuery', async (importOriginal) => {
  const actual = await importOriginal<
    typeof import('@/app/store/features/audit/productAuditQuery')
  >();

  return {
    ...actual,
    useAddProductAuditMutation: vi.fn(() => [vi.fn(), { isLoading: false }]),
  };
});

const products = [
  { id: 11, name: 'Maize Flour 1kg', sku: 'MF-1', quantity: 40 },
  { id: 12, name: 'Sugar 1kg', sku: 'SG-1', quantity: 8 },
];

beforeEach(() => {
  (useProductsQuery as unknown as ReturnType<typeof vi.fn>).mockReturnValue({
    data: { message: 'Products fetched', products },
  });
});

/** Branch, then Status, then one select per audit item. */
const PRODUCT_SELECT = 2;

const openProductSelect = async () => {
  const user = userEvent.setup();

  render(
    <Provider store={store}>
      <MemoryRouter>
        <CreateProductAudit branches={[{ id: 1, name: 'Main Branch' }]} />
      </MemoryRouter>
    </Provider>
  );

  await user.click(screen.getByRole('button', { name: /new product audit/i }));
  await user.click(screen.getAllByRole('combobox')[PRODUCT_SELECT]);

  return user;
};

describe('the product audit form', () => {
  it('offers every product the endpoint returned', async () => {
    await openProductSelect();

    expect(await screen.findByRole('option', { name: /Maize Flour 1kg/ })).toBeTruthy();
    expect(screen.getByRole('option', { name: /Sugar 1kg/ })).toBeTruthy();
  });

  /**
   * The count is the assertion that matters. It is not enough that the select opens:
   * the reported bug was a select that opened and held nothing.
   */
  it('is not empty when the list loads', async () => {
    await openProductSelect();

    expect(await screen.findAllByRole('option')).toHaveLength(products.length);
  });
});