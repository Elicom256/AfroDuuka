import { describe, expect, it } from 'vitest';

import { describeApiError, firstFieldError, formatApiError, serverMessage } from './errorMessage';

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

  it('reads the debug exception the API attaches in debug mode', () => {
    const info = describeApiError({
      status: 500,
      data: {
        message: 'Something went wrong on our side. Please try again.',
        code: 'server_error',
        request_id: '01J8ZZZZZZZZZZZZZZZZZZZZZZ',
        debug: {
          exception: 'Symfony\\Component\\Mailer\\Exception\\TransportException',
          message: 'Request to the Resend API failed.',
        },
      },
    });

    expect(info.debug).toBe(
      '[Symfony\\Component\\Mailer\\Exception\\TransportException] Request to the Resend API failed.'
    );
  });

  it('has no debug detail in a production-shaped payload', () => {
    expect(describeApiError({ status: 500, data: { message: 'Oops.', code: 'server_error' } }).debug).toBeUndefined();
  });
});

describe('formatApiError', () => {
  it('prefers the real exception and appends the correlation id', () => {
    expect(
      formatApiError({
        status: 500,
        data: { message: 'Something went wrong.', request_id: 'abc', debug: { exception: 'X', message: 'boom' } },
      })
    ).toBe('[X] boom (ref: abc)');
  });

  it('keeps the human message and still appends the correlation id', () => {
    expect(formatApiError({ status: 500, data: { message: 'Oops.', request_id: 'abc' } })).toBe(
      'Oops. (ref: abc)'
    );
  });

  it('uses the fallback when there is no body, without a dangling ref', () => {
    expect(formatApiError({ status: 'FETCH_ERROR' }, 'Could not reach the server.')).toBe(
      'Could not reach the server.'
    );
  });
});
