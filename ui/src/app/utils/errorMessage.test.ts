import { describe, expect, it } from 'vitest';

import { describeApiError, firstFieldError, serverMessage } from './errorMessage';

describe('describeApiError', () => {
  it('reads the envelope the API error contract sends', () => {
    const info = describeApiError({
      status: 422,
      data: {
        message: 'The given data was invalid.',
        code: 'validation_error',
        request_id: '01J8ZZZZZZZZZZZZZZZZZZZZZZ',
        errors: { email: ['The email has already been taken.'] },
      },
    });

    expect(info).toEqual({
      message: 'The given data was invalid.',
      code: 'validation_error',
      status: 422,
      fieldErrors: { email: ['The email has already been taken.'] },
      requestId: '01J8ZZZZZZZZZZZZZZZZZZZZZZ',
    });
  });

  it('falls back for a transport failure with no body', () => {
    const info = describeApiError({ status: 'FETCH_ERROR', error: 'Failed to fetch' }, 'Could not reach the server.');

    expect(info.message).toBe('Could not reach the server.');
    expect(info.fieldErrors).toEqual({});
    expect(info.requestId).toBeUndefined();
  });

  it('normalises a single string field error into an array', () => {
    const info = describeApiError({ data: { errors: { phone: 'The phone is required.' } } });

    expect(info.fieldErrors).toEqual({ phone: ['The phone is required.'] });
  });

  it('never returns an empty message', () => {
    expect(describeApiError(undefined).message).not.toBe('');
    expect(describeApiError({ data: { message: '' } }).message).not.toBe('');
  });

  it('exposes the first field error and the server message helpers', () => {
    const error = { data: { message: 'Invalid.', errors: { a: ['first problem'], b: ['second'] } } };

    expect(firstFieldError(error)).toBe('first problem');
    expect(serverMessage(error, 'fallback')).toBe('Invalid.');
    expect(serverMessage({ error: 'boom' }, 'fallback')).toBe('fallback');
  });
});
