/**
 * One place that turns anything a request can reject with into a sentence a person can read.
 *
 * RTK's `unwrap()` rejects with `{ data: { message, code, errors, request_id } }` for a JSON
 * error body and with a bare `{ error }` for a transport failure. The previous helper read
 * only `error.data.message`, so a transport failure produced a blank toast and the
 * `request_id` / `code` the API now sends were thrown away. Everything that needs to show
 * an error goes through `describeApiError` so the copy, the field errors and the support
 * reference come from one contract.
 */

export type ApiErrorInfo = {
  /** Always safe to show. Never empty. */
  message: string;
  /** Stable server slug (e.g. `duplicate`, `forbidden`) when the API sent one. */
  code?: string;
  status?: number;
  /** Field-keyed validation messages, normalised to arrays. */
  fieldErrors: Record<string, string[]>;
  /** Correlation id a user can quote to support. */
  requestId?: string;
};

const DEFAULT_FALLBACK = 'Something went wrong. Please try again.';

const asRecord = (value: unknown): Record<string, unknown> | undefined =>
  typeof value === 'object' && value !== null ? (value as Record<string, unknown>) : undefined;

const normaliseFieldErrors = (value: unknown): Record<string, string[]> => {
  const errors = asRecord(value);

  if (!errors) {
    return {};
  }

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [
      field,
      Array.isArray(messages) ? messages.map(String) : [String(messages)],
    ]),
  );
};

export const describeApiError = (error: unknown, fallback: string = DEFAULT_FALLBACK): ApiErrorInfo => {
  const envelope = asRecord(error);
  const data = asRecord(envelope?.data);
  const rawStatus = envelope?.status;

  const status =
    typeof rawStatus === 'number' ? rawStatus : typeof rawStatus === 'string' ? Number(rawStatus) : undefined;

  const message =
    typeof data?.message === 'string' && data.message.length > 0 ? data.message : fallback;

  const code = typeof data?.code === 'string' && data.code.length > 0 ? data.code : undefined;

  const requestId =
    typeof data?.request_id === 'string' && data.request_id.length > 0 ? data.request_id : undefined;

  return {
    message,
    code,
    status: Number.isFinite(status) ? status : undefined,
    fieldErrors: normaliseFieldErrors(data?.errors),
    requestId,
  };
};

/**
 * The message a server put on a failed request, or a caller-supplied fallback.
 *
 * Kept as a thin wrapper because a large number of call sites and tests use it; new
 * code should prefer `describeApiError` when it also needs the field errors or the id.
 */
export const serverMessage = (error: unknown, fallback: string): string =>
  describeApiError(error, fallback).message;

/** The first field-level validation message, if the server sent any. */
export const firstFieldError = (error: unknown): string | undefined =>
  Object.values(describeApiError(error).fieldErrors)[0]?.[0];
