<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreTodoRequest;
use App\Models\Todo;
use App\Models\User;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Replays an offline todo through the same request the online form uses.
 *
 * StoreTodoRequest merges the owner and business from the authenticated user and
 * forces status to `undone`, so a device cannot attribute a todo to another user
 * or backdate its completion state through the sync payload.
 */
class TodoSyncHandler implements SyncHandler
{
    public function type(): string
    {
        return 'todo';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync todos.');
        }

        $validated = ValidatesPayload::run(StoreTodoRequest::class, $payload);

        $todo = Todo::create($validated);

        return [
            'server_id' => (int) $todo->getKey(),
            'resource_type' => 'todo',
            'result' => [
                'id' => (int) $todo->getKey(),
                'title' => $todo->title,
            ],
        ];
    }
}
