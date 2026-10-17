import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';

import { OperationsWorkersPage } from './OperationsWorkersPage';
import { useBranchWorkersQuery } from '@/app/store/features/branch';

vi.mock('@/app/store/features/branch', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/app/store/features/branch')>();
  return { ...actual, useBranchWorkersQuery: vi.fn() };
});

const mockQuery = (result: unknown) =>
  vi.mocked(useBranchWorkersQuery).mockReturnValue(result as ReturnType<typeof useBranchWorkersQuery>);

const renderPage = () =>
  render(
    <MemoryRouter>
      <OperationsWorkersPage />
    </MemoryRouter>
  );

/**
 * The list guard class from now.md. `const workers = data?.data` left the value
 * `undefined` whenever the payload did not carry a `data` array, and the very next use
 * — `workers?.filter(...).length` — dereferenced it, so the page threw on render and
 * went blank instead of showing its empty state. The page must render for any shape.
 */
describe('OperationsWorkersPage list guards', () => {
  beforeEach(() => {
    vi.mocked(useBranchWorkersQuery).mockReset();
  });

  it('renders an empty state when the payload has no data array', () => {
    mockQuery({ data: { data: undefined }, isLoading: false });

    renderPage();

    expect(screen.getByText('No found for this branch.')).toBeDefined();
  });

  it('renders an empty state when data is missing entirely', () => {
    mockQuery({ data: undefined, isLoading: false });

    renderPage();

    expect(screen.getByText('No found for this branch.')).toBeDefined();
  });

  it('renders the workers it is given', () => {
    mockQuery({
      data: { data: [{ id: 1, status: 'active', username: 'Amina', email: 'a@b.co' }] },
      isLoading: false,
    });

    renderPage();

    expect(screen.getByText('Amina')).toBeDefined();
  });
});
