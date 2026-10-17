<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\CashFlowService;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An expense and its cash-flow row are one fact, same as ExpenseController.
 * Delete stays refused so a synced expense cannot disappear out from under the ledger.
 */
class ExpenseSyncHandler implements SyncHandler
{
    public function __construct(
        private readonly CashFlowService $cashFlows,
        private readonly ActivityLogService $activityLog,
    ) {
    }

    public function type(): string
    {
        return 'expense';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync expenses.');
        }

        $action = SyncAction::name($payload);

        if ($action === 'delete') {
            throw ValidationException::withMessages([
                'action' => 'Expenses cannot be deleted once recorded. Record a reversing entry instead.',
            ]);
        }

        if ($action === 'approve') {
            $expense = Expense::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('approve', $expense)) {
                throw new AuthorizationException('Your role cannot approve expenses.');
            }
            $expense->update(['status' => 'approved']);
            $this->activityLog->activity('approved_expense', "Approved expense ID {$expense->id}", subject: $expense);

            return SyncAction::result('expense', (int) $expense->getKey());
        }

        if ($action === 'update') {
            $expense = Expense::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('update', $expense)) {
                throw new AuthorizationException('Your role cannot update expenses.');
            }
            $validated = ValidatesPayload::run(UpdateExpenseRequest::class, SyncAction::body($payload));
            DB::transaction(function () use ($expense, $validated) {
                $expense->update($validated);
                $cashFlow = $expense->cashFlow;
                if ($cashFlow) {
                    $cashFlow->update([
                        'amount' => $validated['amount'] ?? $expense->amount,
                        'transaction_date' => $validated['payment_date'] ?? $expense->payment_date?->toDateString(),
                    ]);
                }
            });

            return SyncAction::result('expense', (int) $expense->getKey());
        }

        if (! $user->can('create', Expense::class)) {
            throw new AuthorizationException('Your role cannot record expenses.');
        }

        $validated = ValidatesPayload::run(StoreExpenseRequest::class, SyncAction::body($payload));
        $expense = DB::transaction(function () use ($validated) {
            $expense = Expense::create($validated);
            $this->cashFlows->createCashFlowForExpense($expense, (float) $validated['amount']);

            return $expense;
        });
        $this->activityLog->activity('Recorded Expense', "Expense of {$validated['amount']} recorded");

        return SyncAction::result('expense', (int) $expense->getKey());
    }
}
