import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';

import { AiChat } from './AiChat';
import { store } from '@/app/store/app/store';
import { aiQuery } from '@/app/store/features/ai/aiQuery';
import { authQuery } from '@/app/store/features/auth/authQuery';

/**
 * The chat was a visual-only stream: a reply arriving was silent to a screen
 * reader, and the icon-only send button had no accessible name. These pin the
 * announcement flow (findings §8.5) rather than the visual transcript.
 */

const stubFetch = () => {
  const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
    const url = typeof input === 'string' ? input : input instanceof Request ? input.url : String(input);
    const json = (body: unknown) =>
      new Response(JSON.stringify(body), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      });

    if (url.includes('/users/me')) {
      return json({ data: { username: 'Alice', business: { name: 'Duuka' } } });
    }
    if (url.includes('/ai/chat')) {
      return json({ success: true, data: { message: 'You have 4 products.' } });
    }
    return json({ data: {} });
  });
  vi.stubGlobal('fetch', fetchMock);
  return fetchMock;
};

const renderChat = () =>
  render(
    <Provider store={store}>
      <AiChat />
    </Provider>
  );

beforeEach(() => {
  store.dispatch(aiQuery.util.resetApiState());
  store.dispatch(authQuery.util.resetApiState());
});

describe('AiChat screen-reader support', () => {
  it('announces the assistant reply through a polite live region', async () => {
    stubFetch();
    const user = userEvent.setup();

    renderChat();

    await user.type(screen.getByLabelText('Message to the AI assistant'), 'how many products?');
    await user.click(screen.getByRole('button', { name: /send message/i }));

    // The reply also lands in the transcript; this asserts the single live
    // region carries the same text, which is what a screen reader announces.
    await waitFor(() => {
      expect(screen.getByRole('status')).toHaveTextContent('You have 4 products.');
    });
  });

  it('names the send button instead of leaving it as an unlabelled icon', () => {
    stubFetch();
    renderChat();

    expect(screen.getByRole('button', { name: 'Send message' })).toBeInTheDocument();
  });
});
