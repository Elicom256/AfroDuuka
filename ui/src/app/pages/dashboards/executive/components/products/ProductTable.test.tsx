import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { createMemoryRouter, MemoryRouter, RouterProvider } from 'react-router-dom';

import { ProductTable } from './ProductTable';
import { store } from '@/app/store/app/store';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';

/**
 * The delete button is gated on canDelete, which comes from the signed-in user's role.
 * With no user loaded it is false and the button is not rendered at all, so a test that
 * does not sign someone in cannot tell a removed button from a hidden one.
 */
vi.mock('@/app/store/features/auth/authQuery', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/app/store/features/auth/authQuery')>();

  return { ...actual, useLoggedinUserQuery: vi.fn() };
});

/**
 * The products table offered a delete button in its Actions column.
 *
 * bugs.md asks for it to be removed from here and left on the single product's page,
 * which is where a delete belongs: the table row is a dense, repeated control one bad
 * click away from a product nobody meant to remove, and the product page already has
 * its own delete with the product's name in the confirmation.
 *
 * So this pins the absence. It also pins that the edit control survived, because the
 * failure mode of "remove the delete button" done carelessly is a table with no actions
 * at all.
 */

vi.mock('@/app/store/features/branch/products/branchProductsQuery', async (importOriginal) => {
  const actual = await importOriginal<
    typeof import('@/app/store/features/branch/products/branchProductsQuery')
  >();

  return { ...actual, useProductsQuery: vi.fn() };
});

const products = [
  {
    id: '1',
    product_category_id: '1',
    name: 'Maize Flour 1kg',
    sku: 'MF-1',
    barcode: '2433978803',
    selling_price: 1500,
    cost_price: 1000,
    quantity: 40,
    reorder_level: 10,
    minimum_stock: 10,
    status: 'active',
    description: '',
    markup_percentage: 50,
  },
];

beforeEach(() => {
  (useLoggedinUserQuery as unknown as ReturnType<typeof vi.fn>).mockReturnValue({
    data: { data: { role: { name: 'Executive' } } },
  });

  (useProductsQuery as unknown as ReturnType<typeof vi.fn>).mockReturnValue({
    data: { products },
    isLoading: false,
    isFetching: false,
  });
});

/**
 * The delete affordance is an icon-only button with no accessible name, so it cannot be
 * selected by name. The tests select on the lucide icon class instead: `lucide-trash-2`
 * for delete, `lucide-pencil-line` for edit.
 */
const renderTable = () => {
  const { container } = render(
    <Provider store={store}>
      <MemoryRouter>
        <ProductTable />
      </MemoryRouter>
    </Provider>
  );

  return {
    container,
    hasDelete: () => container.querySelector('.lucide-trash-2') !== null,
    hasEdit: () => container.querySelector('.lucide-pencil-line') !== null,
  };
};

/**
 * A real route tree, because MemoryRouter keeps its own history stack and never touches
 * window.location, so a navigation assertion against it can never see the row's click.
 */
const renderTableWithRoutes = () => {
  const router = createMemoryRouter(
    [
      { path: '/', element: <ProductTable /> },
      { path: '/dashboard/products/:id', element: <p>Product page</p> },
    ],
    { initialEntries: ['/'] }
  );

  return { router, ...render(<Provider store={store}><RouterProvider router={router} /></Provider>) };
};

describe('the products table actions column', () => {
  it('offers no delete control', () => {
    const table = renderTable();

    expect(table.hasDelete()).toBe(false);
  });

  it('still offers the edit control', () => {
    const table = renderTable();

    expect(table.hasEdit()).toBe(true);
  });

  /**
   * The row still navigates to the product, which is where delete now lives. Removing the
   * button must not have taken the navigation with it.
   */
  it('still navigates to the product when the row is clicked', async () => {
    renderTableWithRoutes();

    await userEvent.click(screen.getByText('Maize Flour 1kg'));

    expect(await screen.findByText('Product page')).toBeTruthy();
  });
});
