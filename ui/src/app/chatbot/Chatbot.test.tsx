import { describe, expect, it, vi, afterEach } from 'vitest';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';

import { Chatbot } from './Chatbot';
import { WELCOME_MESSAGE } from './knowledge';

/**
 * The chatbot is the only public, unauthenticated surface that reaches TinyFish, so these
 * pin the behaviour a visitor depends on: the launcher is named, the panel opens with the
 * welcome and suggestions, verified questions are answered with no network call at all,
 * replies are announced, and the conversation can be cleared or minimized.
 */

const renderChatbot = () =>
  render(
    <MemoryRouter>
      <Chatbot />
    </MemoryRouter>
  );

const openPanel = async (user: ReturnType<typeof userEvent.setup>) => {
  await user.click(screen.getByRole('button', { name: 'Open AfroDuuka assistant' }));
};

afterEach(() => {
  vi.unstubAllGlobals();
});

describe('Chatbot launcher and panel', () => {
  it('names the launcher and opens the labelled dialog', async () => {
    const user = userEvent.setup();
    renderChatbot();

    const launcher = screen.getByRole('button', { name: 'Open AfroDuuka assistant' });
    expect(launcher).toHaveAttribute('aria-expanded', 'false');

    await user.click(launcher);

    expect(screen.getByRole('dialog', { name: 'AfroDuuka assistant' })).toBeInTheDocument();
    expect(launcher).toHaveAttribute('aria-expanded', 'true');
  });

  it('welcomes the visitor and offers suggested questions', async () => {
    const user = userEvent.setup();
    renderChatbot();
    await openPanel(user);

    const panel = screen.getByRole('dialog', { name: 'AfroDuuka assistant' });
    expect(within(panel).getAllByText(WELCOME_MESSAGE).length).toBeGreaterThan(0);
    expect(within(panel).getByRole('button', { name: 'What is AfroDuuka?' })).toBeInTheDocument();
    expect(within(panel).getByRole('button', { name: 'Which subscription plans are available?' })).toBeInTheDocument();
  });

  it('minimizes the panel from the header', async () => {
    const user = userEvent.setup();
    renderChatbot();
    await openPanel(user);

    await user.click(screen.getByRole('button', { name: 'Minimize chat' }));

    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Open AfroDuuka assistant' })).toBeInTheDocument();
  });
});

describe('Chatbot answering', () => {
  it('answers a verified question locally without any network call', async () => {
    const fetchMock = vi.fn(() => Promise.reject(new Error('network should not be used')));
    vi.stubGlobal('fetch', fetchMock);

    const user = userEvent.setup();
    renderChatbot();
    await openPanel(user);

    await user.click(screen.getByRole('button', { name: 'What is AfroDuuka?' }));

    const panel = screen.getByRole('dialog', { name: 'AfroDuuka assistant' });
    await waitFor(() => {
      expect(within(panel).getAllByText(/smart inventory management software/i).length).toBeGreaterThan(0);
    });

    expect(fetchMock).not.toHaveBeenCalled();
    expect(within(panel).getByRole('link', { name: 'Get started' })).toHaveAttribute('href', '/onboarding');
  });

  it('announces the assistant reply through the polite status region', async () => {
    const user = userEvent.setup();
    renderChatbot();
    await openPanel(user);

    await user.click(screen.getByRole('button', { name: 'What is AfroDuuka?' }));

    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent(/smart inventory management software/i);
    });
  });

  it('sends a typed question on Enter and answers it', async () => {
    const user = userEvent.setup();
    renderChatbot();
    await openPanel(user);

    await user.type(
      screen.getByLabelText('Message to the AfroDuuka assistant'),
      'What features does it offer?{enter}',
    );

    const panel = screen.getByRole('dialog', { name: 'AfroDuuka assistant' });
    await waitFor(() => {
      expect(within(panel).getAllByText(/inventory/i).length).toBeGreaterThan(0);
    });
    expect(within(panel).getByText('What features does it offer?')).toBeInTheDocument();
  });

  it('clears the conversation back to the welcome', async () => {
    const user = userEvent.setup();
    renderChatbot();
    await openPanel(user);

    await user.click(screen.getByRole('button', { name: 'What is AfroDuuka?' }));
    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent(/smart inventory management software/i);
    });

    await user.click(screen.getByRole('button', { name: 'Clear conversation' }));

    const panel = screen.getByRole('dialog', { name: 'AfroDuuka assistant' });
    expect(within(panel).getAllByText(WELCOME_MESSAGE).length).toBeGreaterThan(0);
    expect(within(panel).queryByText('What is AfroDuuka?', { selector: 'div' })).not.toBeInTheDocument();
  });
});

describe('Chatbot live lookups', () => {
  it('quotes a live pricing page and links its source', async () => {
    const passage = 'The starter plan costs UGX 20,000 per month for small shops, billed monthly.';
    const fetchMock = vi.fn(async () =>
      new Response(
        JSON.stringify({
          results: [{ url: 'https://afroduka.com/pricing', title: 'AfroDuuka pricing', text: passage }],
        }),
        { status: 200, headers: { 'Content-Type': 'application/json' } },
      ),
    );
    vi.stubGlobal('fetch', fetchMock);

    const user = userEvent.setup();
    renderChatbot();
    await openPanel(user);

    await user.click(screen.getByRole('button', { name: 'Which subscription plans are available?' }));

    const panel = screen.getByRole('dialog', { name: 'AfroDuuka assistant' });
    await waitFor(() => {
      expect(within(panel).getAllByText(new RegExp(passage.slice(0, 30))).length).toBeGreaterThan(0);
    });

    expect(fetchMock).toHaveBeenCalledWith(expect.stringContaining('/chatbot/fetch'), expect.anything());
    expect(within(panel).getByRole('link', { name: /AfroDuuka pricing/i })).toHaveAttribute(
      'href',
      'https://afroduka.com/pricing',
    );
  });
});
