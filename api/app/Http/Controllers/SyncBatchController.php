<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncBatchRequest;
use App\Services\Sync\SyncOperationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POST /sync/batch — the offline outbox's submission endpoint.
 *
 * Operations are processed independently and sequentially: the batch is accepted as
 * a whole (envelope validated once), then each operation produces its own
 * `synced`/`duplicate`/`conflict`/`failed` entry. One operation's rejection never
 * blocks the others, and no operation reports success before its database
 * transaction has committed (see SyncOperationDispatcher).
 *
 * Returns 200 for a processed batch regardless of per-operation outcomes — the
 * caller decides per result. Malformed envelopes are rejected up front with 422.
 */
class SyncBatchController extends Controller
{
    public function store(SyncBatchRequest $request, SyncOperationDispatcher $dispatcher): JsonResponse
    {
        $user = $request->user();
        $results = [];
        $summary = [
            'synced' => 0,
            'duplicate' => 0,
            'conflict' => 0,
            'failed' => 0,
        ];

        foreach ($request->validated('operations') as $operation) {
            try {
                $result = $dispatcher->dispatch($operation, $user);
            } catch (Throwable $e) {
                // The dispatcher is designed not to throw; this guard exists so a
                // bug in one operation cannot fail the whole batch.
                Log::error('sync batch operation crashed', [
                    'operation_id' => $operation['operation_id'] ?? null,
                    'type' => $operation['type'] ?? null,
                    'business_id' => $user->business_id,
                    'exception_class' => $e::class,
                    'exception_code' => (string) $e->getCode(),
                ]);

                $result = [
                    'operation_id' => $operation['operation_id'],
                    'status' => 'failed',
                    'error' => [
                        'code' => 'processing_error',
                        'message' => 'The operation could not be processed.',
                        'details' => [],
                    ],
                ];
            }

            $results[] = $result;
            $summary[$result['status']] = ($summary[$result['status']] ?? 0) + 1;
        }

        return response()->json([
            'message' => 'Sync batch processed',
            'results' => $results,
            'summary' => $summary,
        ], 200);
    }
}
