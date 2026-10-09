/**
 * The message a server put on a failed request, or a caller-supplied fallback.
 *
 * RTK's unwrap rejects with `{ data: { message } }` for a JSON error body and with a
 * bare `{ error }` for a transport failure, so reaching for `error.data.message`
 * directly throws on the second shape and the user sees a blank toast.
 */
export const serverMessage = (error: unknown, fallback: string): string => {
  if (typeof error !== 'object' || error === null) {
    return fallback;
  }

  const data = (error as { data?: unknown }).data;

  if (typeof data === 'object' && data !== null) {
    const message = (data as { message?: unknown }).message;

    if (typeof message === 'string' && message.length > 0) {
      return message;
    }
  }

  return fallback;
};
