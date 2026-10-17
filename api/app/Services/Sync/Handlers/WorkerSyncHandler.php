<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Models\User;
use App\Models\Worker;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use App\Services\WorkerService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Worker create and update go through WorkerService. Destroy stays refused:
 * the online worker route answers 405 and removal is UserController::destroy.
 */
class WorkerSyncHandler implements SyncHandler
{
    public function __construct(private readonly WorkerService $workers)
    {
    }

    public function type(): string
    {
        return 'worker';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync workers.');
        }

        $action = SyncAction::name($payload);

        if ($action === 'delete') {
            throw ValidationException::withMessages([
                'action' => 'Workers cannot be deleted from this operation. Remove the user account instead.',
            ]);
        }

        if ($action === 'update') {
            $worker = Worker::query()->findOrFail(SyncAction::id($payload));
            $validated = ValidatesPayload::run(UpdateWorkerRequest::class, SyncAction::body($payload));
            $updated = $this->workers->updateWorker($worker, $validated);

            return SyncAction::result('worker', (int) $updated->getKey());
        }

        $validated = ValidatesPayload::run(StoreWorkerRequest::class, SyncAction::body($payload));
        $worker = $this->workers->addWorker($validated);

        return SyncAction::result('worker', (int) $worker->getKey(), [
            'employee_code' => $worker->employee_code,
        ]);
    }
}
