<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Models\Todo;
use App\Models\User;
use App\Services\Sync\SyncAction;
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

        $action = SyncAction::name($payload);

        if ($action === 'delete') {
            $todo = Todo::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('delete', $todo)) {
                throw new AuthorizationException('Your role cannot delete this task.');
            }
            $id = (int) $todo->getKey();
            $todo->delete();

            return SyncAction::result('todo', $id);
        }

        if ($action === 'update') {
            $todo = Todo::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('update', $todo)) {
                throw new AuthorizationException('Your role cannot update this task.');
            }
            $validated = ValidatesPayload::run(UpdateTodoRequest::class, SyncAction::body($payload));
            $todo->update($validated);

            return SyncAction::result('todo', (int) $todo->getKey(), [
                'title' => $todo->title,
            ]);
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
