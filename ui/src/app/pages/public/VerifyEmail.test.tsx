import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { VerifyEmail } from './VerifyEmail';
import { store } from '@/app/store/app/store';
import { authQuery } from '@/app/store/features/auth/authQuery';
import { setToken } from '@/lib/session';
import { rememberVerificationEmail } from '@/lib/verification';

/**
 * The three faces of /verify-email and the resend affordance.
 *
 * Rendered on its own rather than through AppRoutes: AppRoutes adds the role tree on
 * top, and a failure there would be indistinguishable from a failure here. The route
 * itself is covered by publicRoutes.test.tsx, which walks PUBLIC_PATHS.
 */

const requestUrl = (input: RequestInfo | URL): string => {
  if (typeof input === 'string') return input;
  if (typeof URL !== 'undefined' && input instanceof URL) return input.toString();
  if (typeof Request !== 'undefined' && input instanceof Request) return input.url;
  return String(input);
};

const requestMethod = (input: RequestInfo | URL): string => {
  if (typeof Request !== 'undefined' && input instanceof Request) return input.method.toUpperCase();
  return 'GET';
};

interface StubOptions {
  verifyStatus?: number;
  meStatus?: number;
}

const stubFetch = ({ verifyStatus = 200, meStatus = 401 }: StubOptions = {}) => {
  const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
    const url = requestUrl(input);
    const method = requestMethod(input);
    const json = (body: unknown, status = 200) =>
      new Response(JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
      });

    if (url.includes('/verify-email/resend') && method === 'POST') {
      return json({ message: 'Verification email sent' });
    }

    if (url.includes('/verify-email')) {
      return verifyStatus < 400
        ? json({ message: 'Email verified successfully. Your business is now active.' })
        : json({ message: 'Invalid or expired verification token' }, verifyStatus);
    }

    if (url.includes('/users/me')) {
      return meStatus < 400
        ? json({
            message: 'User retrieved successfully',
            data: { business: { email: 'amina@example.com' } },
          })
        : json({ message: 'Unauthenticated.' }, meStatus);
    }

    return json({ data: {} });
  });

  vi.stubGlobal('fetch', fetchMock);
  return fetchMock;
};

const renderVerify = (path: string) =>
  render(
    <Provider store={store}>
      <MemoryRouter initialEntries={[path]}>
        <VerifyEmail />
      </MemoryRouter>
    </Provider>
  );

const postCalls = (fetchMock: ReturnType<typeof stubFetch>) =>
  fetchMock.mock.calls.filter(
    ([input]) => requestUrl(input).includes('/verify-email/resend') && requestMethod(input) === 'POST'
  );

beforeEach(() => {
  store.dispatch(authQuery.util.resetApiState());
});

describe('the check-inbox state', () => {
  it('prompts the owner to open the email when no token is present', async () => {
    stubFetch();

    renderVerify('/verify-email');

    expect(await screen.findByText('Check your inbox')).toBeTruthy();
  });

  it('names the address remembered at the end of onboarding', async () => {
    stubFetch();
    rememberVerificationEmail('owner@shop.test');

    renderVerify('/verify-email');

    expect(await screen.findByText(/owner@shop\.test/)).toBeTruthy();
  });

  it('offers a sign-in path instead of a resend when there is no session', async () => {
    stubFetch();

    renderVerify('/verify-email');

    expect(await screen.findByText(/to send another verification email/i)).toBeTruthy();
  });
});

describe('following an emailed link', () => {
  it('reports success for a valid token', async () => {
    stubFetch({ verifyStatus: 200 });

    renderVerify('/verify-email?token=good-token');

    expect(await screen.findByText('Email verified')).toBeTruthy();
  });

  it('reports an invalid or expired token rather than a false success', async () => {
    stubFetch({ verifyStatus: 404 });

    renderVerify('/verify-email?token=stale-token');

    expect(await screen.findByText('Link expired')).toBeTruthy();
  });

  it('shows the error the api bounced back on the query string', async () => {
    stubFetch();

    renderVerify('/verify-email?error=' + encodeURIComponent('Verification token is required'));

    expect(await screen.findByText('Verification failed')).toBeTruthy();
  });
});

describe('resending', () => {
  it('sends one email and then holds the button for the cooldown', async () => {
    const fetchMock = stubFetch({ meStatus: 200 });
    setToken('live.token');

    const user = userEvent.setup();
    renderVerify('/verify-email');

    const button = await screen.findByRole('button', { name: /resend email/i });

    await user.click(button);

    await waitFor(() => {
      expect(postCalls(fetchMock)).toHaveLength(1);
    });

    // The local countdown mirrors the server's 60s cooldown, so the button cannot be
    // pressed again into a silently dropped resend.
    const held = await screen.findByRole('button', { name: /resend in \d+s/i });
    expect(held).toBeDisabled();
  });
});
