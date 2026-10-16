<?php

namespace App\Services\Sync;

use App\Models\User;

/**
 * Processes one claimed sync operation of a single type.
 *
 * Implementations run inside the dispatcher's per-operation transaction: everything
 * `handle()` writes commits atomically with the SyncOperation flip to `completed`,
 * or rolls back together when it throws. Throwing SyncConflictException marks a
 * conflict; throwing ValidationException (typically from ValidatesPayload) marks a
 * validation failure; anything else becomes a classified failure.
 */
interface SyncHandler
{
    /**
     * The operation type this handler serves (sale, inventory, customer, todo).
     */
    public function type(): string;

    /**
     * Apply the operation's business effects.
     *
     * @param  array<string, mixed>  $payload  the operation payload, as queued by the outbox
     * @return array{server_id: int, resource_type: string, result: array<string, mixed>}
     *
     * @throws SyncConflictException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(array $payload, User $user, string $operationId): array;
}
