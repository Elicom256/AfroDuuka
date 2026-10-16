<?php

namespace App\Services\Sync;

use App\Exceptions\InsufficientStockException;
use App\Models\SyncOperation;
use App\Models\User;
use App\Services\Sync\Handlers\CustomerSyncHandler;
use App\Services\Sync\Handlers\InventorySyncHandler;
use App\Services\Sync\Handlers\SaleSyncHandler;
use App\Services\Sync\Handlers\TodoSyncHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Exception;
use Throwable;

/**
 * Applies one operation at a time, independently, with durable idempotency.
 *
 * The per-operation sequence is deliberate:
 *
 *  1. **Claim** — insert the SyncOperation row (`processing`) in its own committed
 *     statement. The (business_id, operation_id) unique constraint is what makes
 *     concurrent duplicates safe: the loser of the race gets a PostgreSQL unique
 *     violation and is routed to the stored result instead of a second business run.
 *  2. **Execute** — run the type handler and flip the claim to `completed` inside
 *     ONE transaction, so the completion marker and the business effects commit
 *     together. A crash either leaves `processing` with nothing applied (stale —
 *     safe to re-claim) or `completed` with everything applied (replay returns the
 *     stored result). There is no state where a sale exists unmarked or a mark
 *     exists without its sale.
 *  3. **On failure** — the business transaction has rolled back; record `failed`
 *     with a stable error code in a separate write so the operation stays visible
 *     and retryable.
 *
 * Nothing here throws: one operation's rejection is a per-operation result, never
 * a batch failure. Success is only reported after the completion transaction has
 * committed.
 */
class SyncOperationDispatcher
{
    /**
     * Operation type => handler class. Resolved lazily via the container so a type
     * without a handler yields a clean per-operation failure rather than a 500, and
     * so handlers receive their constructor dependencies normally.
     */
    private const HANDLERS = [
        'sale' => SaleSyncHandler::class,
        'inventory' => InventorySyncHandler::class,
        'customer' => CustomerSyncHandler::class,
        'todo' => TodoSyncHandler::class,
    ];

    /**
     * @param  array{operation_id: string, type: string, payload: array<string, mixed>, created_at?: ?string}  $operation
     * @return array<string, mixed> the per-operation result entry for the response
     */
    public function dispatch(array $operation, User $user): array
    {
        $operationId = $operation['operation_id'];
        $type = $operation['type'];
        $startedAt = microtime(true);

        try {
            $claim = $this->claim($operation, $user);
        } catch (Throwable $e) {
            Log::error('sync operation claim failed', [
                'operation_id' => $operationId,
                'type' => $type,
                'business_id' => $user->business_id,
                'exception' => $e,
            ]);

            return $this->failedResult($operationId, 'processing_error', 'Could not claim the operation for processing.');
        }

        if ($claim['kind'] === 'duplicate') {
            /** @var SyncOperation $record */
            $record = $claim['record'];

            return [
                'operation_id' => $operationId,
                'status' => 'duplicate',
                'server_id' => $record->server_resource_id,
                'result' => $record->result,
            ];
        }

        if ($claim['kind'] === 'in_flight') {
            return $this->failedResult($operationId, 'in_flight', 'This operation is already being processed; retry after a delay.');
        }

        /** @var SyncOperation $record */
        $record = $claim['record'];
        $handlerClass = self::HANDLERS[$type] ?? null;

        if ($handlerClass === null || ! class_exists($handlerClass)) {
            $message = "No handler is registered for operation type '{$type}'.";

            $this->markFailed($record, 'unsupported_type', $message);

            return $this->failedResult($operationId, 'unsupported_type', $message);
        }

        try {
            /** @var array{server_id: int, resource_type: string, result: array<string, mixed>} $handlerResult */
            $handlerResult = DB::transaction(function () use ($handlerClass, $operation, $user, $operationId, $record) {
                $result = app($handlerClass)->handle($operation['payload'], $user, $operationId);

                $record->update([
                    'status' => SyncOperation::STATUS_COMPLETED,
                    'server_resource_type' => $result['resource_type'],
                    'server_resource_id' => $result['server_id'],
                    'result' => $result['result'],
                    'error_code' => null,
                    'error_message' => null,
                    'processed_at' => now(),
                ]);

                return $result;
            });

            Log::info('sync operation synced', [
                'operation_id' => $operationId,
                'type' => $type,
                'business_id' => $user->business_id,
                'server_id' => $handlerResult['server_id'],
                'attempts' => $record->refresh()->attempts,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            return [
                'operation_id' => $operationId,
                'status' => 'synced',
                'server_id' => $handlerResult['server_id'],
                'result' => $handlerResult['result'],
            ];
        } catch (Throwable $e) {
            $error = $this->classify($e);

            $this->markFailed($record, $error['code'], $error['message']);

            Log::warning('sync operation rejected', [
                'operation_id' => $operationId,
                'type' => $type,
                'business_id' => $user->business_id,
                'status' => $error['status'],
                'error_code' => $error['code'],
                'attempts' => $record->refresh()->attempts,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'exception' => $e,
            ]);

            return [
                'operation_id' => $operationId,
                'status' => $error['status'],
                'error' => [
                    'code' => $error['code'],
                    'message' => $error['message'],
                    'details' => $error['details'] ?? [],
                ],
            ];
        }
    }

    /**
     * Durable claim for this operation id, per the crash-consistency contract in
     * SyncOperation. Never returns an ambiguous record: either this call owns the
     * processing attempt, the stored completed result, or someone else is mid-flight.
     *
     * @param  array{operation_id: string, type: string, payload?: array<string, mixed>, created_at?: ?string}  $operation
     * @return array{kind: 'claimed'|'duplicate'|'in_flight', record: ?SyncOperation}
     */
    private function claim(array $operation, User $user): array
    {
        $attributes = [
            'business_id' => $user->business_id,
            'user_id' => $user->id,
            'operation_id' => $operation['operation_id'],
            'type' => $operation['type'],
            'status' => SyncOperation::STATUS_PROCESSING,
            'attempts' => 1,
            'client_created_at' => $this->clientCreatedAt($operation),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // PostgreSQL's ON CONFLICT avoids aborting an enclosing transaction. That
        // matters in tests and callers that wrap requests in a transaction, while
        // the unique constraint remains the authority for concurrent claims.
        $inserted = DB::table('sync_operations')->insertOrIgnore($attributes);

        if ($inserted === 1) {
            return ['kind' => 'claimed', 'record' => $this->operationScope($operation['operation_id'], $user)->firstOrFail()];
        }

        $existing = $this->operationScope($operation['operation_id'], $user)->first();

        if ($existing === null) {
            // The conflicting row vanished between the violation and this read
            // (business cascade delete). Nothing to process against.
            return ['kind' => 'in_flight', 'record' => null];
        }

        if ($existing->isCompleted()) {
            return ['kind' => 'duplicate', 'record' => $existing];
        }

        // Re-claim a failed attempt or a stale abandoned claim. The guarded UPDATE
        // is atomic: if another request re-claimed first, this affects 0 rows and we
        // must not process concurrently.
        $updates = [
            'status' => SyncOperation::STATUS_PROCESSING,
            'user_id' => $user->id,
            'type' => $operation['type'],
            'error_code' => null,
            'error_message' => null,
            'attempts' => DB::raw('attempts + 1'),
            'updated_at' => now(),
        ];

        if (($clientCreatedAt = $this->clientCreatedAt($operation)) !== null) {
            $updates['client_created_at'] = $clientCreatedAt;
        }

        $reclaimed = $this->operationScope($operation['operation_id'], $user)
            ->where(function ($query) {
                $query->where('status', SyncOperation::STATUS_FAILED)
                    ->orWhere(function ($stale) {
                        $stale->where('status', SyncOperation::STATUS_PROCESSING)
                            ->where('updated_at', '<', now()->subMinutes(SyncOperation::STALE_MINUTES));
                    });
            })
            ->update($updates);

        if ($reclaimed === 0) {
            return ['kind' => 'in_flight', 'record' => null];
        }

        return ['kind' => 'claimed', 'record' => $this->operationScope($operation['operation_id'], $user)->firstOrFail()];
    }

    private function operationScope(string $operationId, User $user): \Illuminate\Database\Eloquent\Builder
    {
        return SyncOperation::query()
            ->where('business_id', $user->business_id)
            ->where('operation_id', $operationId);
    }

    private function clientCreatedAt(array $operation): ?string
    {
        if (empty($operation['created_at'])) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($operation['created_at'])->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Record the failure outside the rolled-back business transaction so the
     * operation stays visible and retryable. Must never mask the original error.
     */
    private function markFailed(SyncOperation $record, string $code, string $message): void
    {
        try {
            $record->update([
                'status' => SyncOperation::STATUS_FAILED,
                'error_code' => $code,
                'error_message' => mb_substr($message, 0, 2000),
                'processed_at' => null,
            ]);
        } catch (Throwable $e) {
            Log::critical('failed to record sync operation failure', [
                'sync_operation_id' => $record->id,
                'operation_id' => $record->operation_id,
                'error_code' => $code,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Map a thrown condition to the response contract's status + a stable,
     * machine-readable code. Business messages are safe to surface (they are the
     * same texts the online endpoints report); unexpected exceptions are logged in
     * full and reported generically so internals never leave the API.
     *
     * @return array{status: 'conflict'|'failed', code: string, message: string, details?: array}
     */
    private function classify(Throwable $e): array
    {
        if ($e instanceof SyncConflictException) {
            return [
                'status' => 'conflict',
                'code' => $e->errorCode,
                'message' => $e->getMessage(),
                'details' => $e->details,
            ];
        }

        if ($e instanceof InsufficientStockException) {
            return [
                'status' => 'conflict',
                'code' => 'insufficient_stock',
                'message' => $e->getMessage(),
                'details' => $e->context(),
            ];
        }

        if ($e instanceof ValidationException) {
            return [
                'status' => 'failed',
                'code' => 'validation_error',
                'message' => 'The operation payload failed validation.',
                'details' => $e->errors(),
            ];
        }

        if ($e instanceof AuthorizationException) {
            return [
                'status' => 'failed',
                'code' => 'forbidden',
                // Handlers raise AuthorizationException with the same human-readable
                // texts the online endpoints abort with (e.g. a disabled feature);
                // policy denials arrive with an empty message and get the generic one.
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Not authorized to perform this operation.',
                'details' => [],
            ];
        }

        if ($e instanceof ModelNotFoundException) {
            return [
                'status' => 'failed',
                'code' => 'not_found',
                'message' => 'A referenced record was not found.',
                'details' => [],
            ];
        }

        if ($e instanceof InvalidArgumentException) {
            return [
                'status' => 'failed',
                'code' => 'invalid_argument',
                'message' => $e->getMessage(),
                'details' => [],
            ];
        }

        if ($e instanceof Exception) {
            $status = (int) $e->getCode();

            if ($status === 403) {
                return [
                    'status' => 'failed',
                    'code' => 'forbidden',
                    'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Not authorized to perform this operation.',
                    'details' => [],
                ];
            }

            if ($status === 404) {
                return [
                    'status' => 'failed',
                    'code' => 'not_found',
                    'message' => 'A referenced record was not found.',
                    'details' => [],
                ];
            }

            if ($status === 422) {
                // Domain rejections raised by the existing services (cart validation
                // and similar) carry actionable messages and stay client-safe.
                return [
                    'status' => 'failed',
                    'code' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => [],
                ];
            }
        }

        Log::error('sync operation unexpected exception', ['exception' => $e]);

        return [
            'status' => 'failed',
            'code' => 'processing_error',
            'message' => 'The operation could not be processed.',
            'details' => [],
        ];
    }

    private function failedResult(string $operationId, string $code, string $message): array
    {
        return [
            'operation_id' => $operationId,
            'status' => 'failed',
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => [],
            ],
        ];
    }
}
