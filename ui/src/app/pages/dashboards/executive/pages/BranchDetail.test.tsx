import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';

import { BranchDetail } from './BranchDetail';
import { useBranchQuery, useDeleteBranchMutation } from '@/app/store/features/business/branches/branchesQuery';
import { toast } from 'sonner';

vi.mock('@/app/store/features/business/branches/branchesQuery', async (importOriginal) => {
  const actual = await importOriginal<
    typeof import('@/app/store/features/business/branches/branchesQuery')
  >();

  return {
    ...actual,
    useBranchQuery: vi.fn(),
    useDeleteBranchMutation: vi.fn(),
    useUpdateBranchMutation: vi.fn(() => [vi.fn(), { isLoading: false }]),
  };
});

vi.mock('sonner', () => ({
  toast: { success: vi.fn(), error: vi.fn() },
}));

/**
 * The page's edit control pointed at `/dashboard/branches/:id/edit`, a route that does
 * exist nowhere — it fell through to the 404 catch-all. The delete control had no
 * handler at all: clicking it did nothing, while the API's own refusal (405, "close the
 * branch instead") was never surfaced. Both are now wired: edit opens a dialog prefilled
 * with the branch, delete confirms and then reports whatever the server said.
 */
describe('BranchDetail', () => {
  const branch = {
    id: 7,
    name: 'Kampala Central',
    address: 'Plot 1, Kampala Rd',
    phone: '0770000007',
    status: 'active',
    worker_count: 4,
    product_count: 12,
    total_sales: 250000,
    total_expenses: 40000,
  };

  const deleteBranch = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(useBranchQuery).mockReturnValue({
      data: { branch },
      isLoading: false,
      isError: false,
    } as unknown as ReturnType<typeof useBranchQuery>);
    vi.mocked(useDeleteBranchMutation).mockReturnValue([
      deleteBranch,
      { isLoading: false },
    ] as unknown as ReturnType<typeof useDeleteBranchMutation>);
  });

  const renderPage = () => render(
    <MemoryRouter>
      <BranchDetail />
    </MemoryRouter>
  );

  it('shows the summary figures the API returns instead of zeroes', () => {
    renderPage();

    expect(screen.getByText('4')).toBeDefined();
    expect(screen.getByText('12')).toBeDefined();
    expect(screen.getByText('250000')).toBeDefined();
    expect(screen.getByText('40000')).toBeDefined();
  });

  it('opens a prefilled edit dialog rather than a dead link', async () => {
    const user = userEvent.setup();
    renderPage();

    await user.click(screen.getByRole('button', { name: /edit branch/i }));

    expect(await screen.findByRole('dialog')).toBeDefined();
    expect(screen.getByLabelText('Name')).toHaveProperty('value', 'Kampala Central');
    expect(screen.getByLabelText('Address')).toHaveProperty('value', 'Plot 1, Kampala Rd');
    expect(screen.queryByRole('link', { name: /edit/i })).toBeNull();
  });

  it('confirms before deleting and toasts the server reason when refused', async () => {
    deleteBranch.mockImplementation(() => ({
      unwrap: () =>
        Promise.reject({
          data: { message: 'Branches cannot be deleted once trading has started. Close the branch instead.' },
        }),
    }));

    const user = userEvent.setup();
    renderPage();

    await user.click(screen.getByRole('button', { name: /delete branch/i }));

    expect(await screen.findByText('Delete this branch?')).toBeDefined();

    await user.click(screen.getByRole('button', { name: /^delete$/i }));

    expect(deleteBranch).toHaveBeenCalledWith(7);
    expect(toast.error).toHaveBeenCalledWith(
      'Branches cannot be deleted once trading has started. Close the branch instead.'
    );
  });
});
