import { isStorageFailure } from './db';
import { listOperations } from './outbox';
import { isTransportError, operationContextFromUser, submitOperation, type SubmitResult } from './operationQueue';
import { loadSessionProfile } from './sessionProfile';
import {
  matchMutation,
  requestBody,
  requestMethod,
  requestUrl,
  type MatchedMutation,
} from './mutationRegistry';
import type { FetchArgs } from '@reduxjs/toolkit/query';
import type { OperationType } from './types';

export const OFFLINE_SAVED_MESSAGE = 'Saved on this device — will sync';

export interface OfflineQueuedBody {
  success: true;
  offline: true;
  syncStatus: 'pending';
  message: string;
  local_id: string;
  data: Record<string, unknown>;
}

/**
 * Build the outbox payload for a matched mutation.
 *
 * Flat creates keep the body the existing handlers already validate. Every
 * other action is an envelope so a handler can tell an update from a create
 * without guessing from the presence of an id.
 */
export function buildQueuedPayload(match: MatchedMutation, body: unknown): unknown {
  if (match.route.flat) return body ?? {};
  return {
    action: match.route.action ?? 'create',
    id: match.id,
    body: body ?? null,
  };
}

function sessionUser(): unknown {
  const cached = loadSessionProfile();
  if (!cached || typeof cached !== 'object') return null;
  const data = (cached as { data?: unknown }).data;
  return data && typeof data === 'object' ? data : cached;
}

async function existingPending(type: OperationType, payload: unknown): Promise<SubmitResult | null> {
  const fingerprint = JSON.stringify(payload);
  const pending = await listOperations({ type, status: 'pending' });
  const found = pending.find((operation) => JSON.stringify(operation.payload) === fingerprint);
  if (!found) return null;
  return { queued: true, localId: found.operationId, operation: found };
}

/**
 * Queue a mutation the registry knows, or report that this call should keep
 * the original transport error.
 *
 * A server answer (4xx/5xx) must never reach this function. Callers check
 * `isTransportError` first. An identical pending payload is not queued twice.
 */
export async function queueMutation(
  args: string | FetchArgs,
): Promise<{ data: OfflineQueuedBody } | { error: { status: 'CUSTOM_ERROR'; data: { message: string } } } | null> {
  const match = matchMutation(requestMethod(args), requestUrl(args));
  if (!match) return null;

  const context = operationContextFromUser(sessionUser() as Parameters<typeof operationContextFromUser>[0]);
  if (!context.businessId) {
    return {
      error: {
        status: 'CUSTOM_ERROR',
        data: { message: 'Cannot save offline without a business.' },
      },
    };
  }

  const payload = buildQueuedPayload(match, requestBody(args));

  try {
    const existing = await existingPending(match.route.type, payload);
    const submitted = existing ?? (await submitOperation(match.route.type, payload, context));
    const echoed =
      payload && typeof payload === 'object' && !Array.isArray(payload)
        ? (payload as Record<string, unknown>)
        : {};
    return {
      data: {
        success: true,
        offline: true,
        syncStatus: 'pending',
        message: OFFLINE_SAVED_MESSAGE,
        local_id: submitted.localId,
        data: { ...echoed, id: submitted.localId },
      },
    };
  } catch (error) {
    if (isStorageFailure(error)) {
      return {
        error: {
          status: 'CUSTOM_ERROR',
          data: { message: 'Could not save on this device — storage is full.' },
        },
      };
    }
    return {
      error: {
        status: 'CUSTOM_ERROR',
        data: { message: 'Could not save on this device.' },
      },
    };
  }
}

/**
 * Queue only when the failure is a transport failure.
 *
 * A 422 or 500 answered by the server returns null so the caller keeps that
 * error. Retrying a validation failure offline would just replay the rejection.
 */
export async function queueIfTransportError(
  error: unknown,
  args: string | FetchArgs,
): Promise<{ data: OfflineQueuedBody } | { error: { status: 'CUSTOM_ERROR'; data: { message: string } } } | null> {
  if (!isTransportError(error)) return null;
  return queueMutation(args);
}
