import { describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { UserProfile } from './UserProfile';
import { store } from '@/app/store/app/store';
import { getToken, setToken } from '@/lib/session';
import { navigations } from '@/test/setup';

/**
 * Logging out has to leave the browser signed out.
 *
 * The reported failure was not "logout is broken" — the server revoked the token and
 * the user was redirected to /login correctly. It was that localStorage kept the
 * revoked token, so every later public page load found a token, asked /users/me, got a
 * 401, and redirected to /login. From the user's side, logging out locked them out of
 * the marketing site with no way back.
 *
 * These cover the case that actually occurred: the confirmation request never
 * succeeded. The `finally` block has to clear the token regardless, or the app is back
 * to trusting a credential the server has already thrown away.
 */

const renderProfile = () =>
  render(
    <Provider store={store}>
      <MemoryRouter>
        <UserProfile data={{ data: { name: 'Test User', username: 'tester', email: 't@example.test' } }} />
      </MemoryRouter>
    </Provider>
  );

const openAndLogout = async () => {
  const user = userEvent.setup();

  await user.click(screen.getByRole('button', { name: /open profile and account actions/i }));
  await user.click(screen.getByRole('button', { name: /log out/i }));
};

describe('logging out', () => {
  it('clears the token and navigates to /login when the request succeeds', async () => {
    setToken('valid.token.value');
    vi.stubGlobal(
      'fetch',
      vi.fn(
        async () =>
          new Response(JSON.stringify({ success: true, message: 'Logged out.' }), {
            status: 200,
            headers: { 'Content-Type': 'application/json' },
          })
      )
    );

    renderProfile();
    await openAndLogout();

    await waitFor(() => {
      expect(getToken()).toBeNull();
    });
    expect(navigations).toEqual(['/login']);
  });

  it('still clears the token when the logout request fails', async () => {
    // The regression. Previously a failed request left the token in place, so the app
    // continued to present a dead credential as a live session and every public page
    // redirected to /login. Refusing to log out is bad; logging out locally while the
    // network is down is strictly better.
    setToken('valid.token.value');
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => {
        throw new TypeError('Failed to fetch');
      })
    );

    renderProfile();
    await openAndLogout();

    await waitFor(() => {
      expect(getToken()).toBeNull();
    });
    expect(navigations).toEqual(['/login']);
  });

  it('still clears the token when the server rejects the logout with a 401', async () => {
    // A 401 on /logout means the token was already dead. There is nothing to keep.
    setToken('already.dead.token');
    vi.stubGlobal(
      'fetch',
      vi.fn(
        async () =>
          new Response(JSON.stringify({ message: 'Unauthenticated.' }), {
            status: 401,
            headers: { 'Content-Type': 'application/json' },
          })
      )
    );

    renderProfile();
    await openAndLogout();

    await waitFor(() => {
      expect(getToken()).toBeNull();
    });
    expect(navigations).toEqual(['/login']);
  });
});
