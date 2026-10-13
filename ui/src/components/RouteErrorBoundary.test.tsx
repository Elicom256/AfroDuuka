import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes, useNavigate } from 'react-router-dom';

import { RouteErrorBoundary } from './RouteErrorBoundary';

/**
 * The reported gap (findings §8.2): the shell boundary sits at the app root, so
 * a screen that throws replaces the whole page. These pin the two properties
 * that make a boundary route-level rather than app-level:
 *
 *   1. chrome outside the boundary survives a throw inside it;
 *   2. navigating to another route clears the error without a manual reset.
 */

const Boom = () => {
  throw new Error('kaboom');
};

const Harness = () => {
  const navigate = useNavigate();
  return (
    <>
      <nav>
        <button onClick={() => navigate('/safe')}>go safe</button>
      </nav>
      {/* Stands in for a layout's <Outlet/>: it persists while the routed screen
          underneath it changes. */}
      <RouteErrorBoundary>
        <Routes>
          <Route path='/' element={<Boom />} />
          <Route path='/safe' element={<p>safe page</p>} />
        </Routes>
      </RouteErrorBoundary>
    </>
  );
};

const renderAt = (path: string) =>
  render(
    <MemoryRouter initialEntries={[path]}>
      <Harness />
    </MemoryRouter>
  );

beforeEach(() => {
  // React and the boundary both log the caught error; that is expected here.
  vi.spyOn(console, 'error').mockImplementation(() => {});
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('RouteErrorBoundary', () => {
  it('contains a throwing screen instead of taking the surrounding chrome with it', () => {
    renderAt('/');

    expect(screen.getByText('Something went wrong on this page')).toBeInTheDocument();
    // The nav sits outside the boundary and must still be there.
    expect(screen.getByRole('button', { name: 'go safe' })).toBeInTheDocument();
  });

  it('recovers on navigation without a manual reset', async () => {
    const user = userEvent.setup();
    renderAt('/');

    expect(screen.getByText('Something went wrong on this page')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'go safe' }));

    expect(screen.getByText('safe page')).toBeInTheDocument();
    expect(screen.queryByText('Something went wrong on this page')).not.toBeInTheDocument();
  });

  it('renders children untouched when nothing throws', () => {
    renderAt('/safe');

    expect(screen.getByText('safe page')).toBeInTheDocument();
  });
});
