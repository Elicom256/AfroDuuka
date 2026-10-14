import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { Onboarding } from './Onboarding';
import { writeDraft } from './onboarding/draft';
import { store } from '@/app/store/app/store';
import { authQuery } from '@/app/store/features/auth/authQuery';
import { setToken } from '@/lib/session';
import { readVerificationEmail } from '@/lib/verification';
import { navigations } from '@/test/setup';

/**
 * Where a finished signup lands.
 *
 * The form used to hard-navigate to /dashboard and toast a welcome, so the owner was
 * dropped into a workspace that was still pending verification with nothing telling them
 * to open the email. It now hands off to /verify-email and remembers the address the
 * link went to.
 *
 * The draft is pre-seeded and the session is faked rather than filling all four steps:
 * this test is about the handoff after a successful save, not about the form widgets,
 * which have their own tests (BusinessSetup.test.tsx).
 */

const requestUrl = (input: RequestInfo | URL): string => {
  if (typeof input === 'string') return input;
  if (typeof URL !== 'undefined' && input instanceof URL) return input.toString();
  if (typeof Request !== 'undefined' && input instanceof Request) return input.url;
  return String(input);
};

const stubFetch = () => {
  const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
    const url = requestUrl(input);
    const json = (body: unknown, status = 200) =>
      new Response(JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
      });

    // A session that has an account but is finishing onboarding: this is the
    // "signed up, walked away, came back" path, so the account step is skipped.
    if (url.includes('/users/me')) return json({ data: { id: 1, email: 'amina@example.com' } });

    if (url.includes('/dashboard/business')) {
      return json(
        {
          message: 'Business created successfully',
          business: { id: 7, name: 'Amina Retail' },
          email_verified: false,
          verification_email: 'sales@amina.test',
        },
        201
      );
    }

    if (url.includes('/countries')) return json({ data: [] });
    if (url.includes('/business-categories')) return json([]);

    return json({ data: {} });
  });

  vi.stubGlobal('fetch', fetchMock);
  return fetchMock;
};

const seedCompletedDraft = () =>
  writeDraft({
    account: {
      firstname: 'Amina',
      lastname: 'Nabirye',
      email: 'amina@example.com',
      phone: '+256700000001',
      password: '',
      confirmPassword: '',
    },
    business: {
      name: 'Amina Retail',
      business_category_id: '1',
      country_id: '1',
      email: 'sales@amina.test',
      phone: '',
      address: 'Plot 1, Kampala Road',
    },
    branches: [{ name: 'Main Branch', address: 'Plot 1, Kampala Road', phone: '' }],
    accountCreated: true,
  });

const renderOnboarding = () =>
  render(
    <Provider store={store}>
      <MemoryRouter>
        <Onboarding />
      </MemoryRouter>
    </Provider>
  );

beforeEach(() => {
  store.dispatch(authQuery.util.resetApiState());
  setToken('live.token');
  seedCompletedDraft();
});

describe('the onboarding handoff', () => {
  it('lands on the check-inbox screen, not the dashboard, after saving', async () => {
    stubFetch();

    const user = userEvent.setup();
    renderOnboarding();

    // The signed-in owner starts on the business step; walk to the preview.
    await user.click(await screen.findByRole('button', { name: /continue to branches/i }));
    await user.click(await screen.findByRole('button', { name: /review setup/i }));
    await user.click(await screen.findByRole('button', { name: /save and go to dashboard/i }));

    await waitFor(() => {
      expect(navigations).toContain('/verify-email');
    });

    expect(navigations).not.toContain('/dashboard');
  });

  it('remembers the address the link was sent to', async () => {
    stubFetch();

    const user = userEvent.setup();
    renderOnboarding();

    await user.click(await screen.findByRole('button', { name: /continue to branches/i }));
    await user.click(await screen.findByRole('button', { name: /review setup/i }));
    await user.click(await screen.findByRole('button', { name: /save and go to dashboard/i }));

    await waitFor(() => {
      expect(readVerificationEmail()).toBe('sales@amina.test');
    });
  });
});
