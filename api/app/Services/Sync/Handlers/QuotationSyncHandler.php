<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationService;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class QuotationSyncHandler implements SyncHandler
{
    public function __construct(private readonly QuotationService $quotations)
    {
    }

    public function type(): string
    {
        return 'quotation';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync quotations.');
        }

        $action = SyncAction::name($payload);

        if (in_array($action, ['update', 'delete', 'send', 'accept', 'cancel'], true)) {
            $quotation = Quotation::query()->findOrFail(SyncAction::id($payload));

            if ($action === 'delete') {
                if (! $user->can('delete', $quotation)) {
                    throw new AuthorizationException('Your role cannot delete quotations.');
                }
                if (! in_array($quotation->status, ['draft', 'sent'], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Only draft or sent quotations can be deleted.',
                    ]);
                }
                $id = (int) $quotation->getKey();
                $quotation->items()->delete();
                $quotation->delete();

                return SyncAction::result('quotation', $id);
            }

            if ($action === 'send') {
                $sent = $this->quotations->send($quotation);

                return SyncAction::result('quotation', (int) $sent->getKey());
            }

            if ($action === 'cancel') {
                $cancelled = $this->quotations->cancel($quotation);

                return SyncAction::result('quotation', (int) $cancelled->getKey());
            }

            if ($action === 'accept') {
                $order = $this->quotations->accept($quotation, $user->business_branch_id);

                return SyncAction::result('quotation', (int) $quotation->getKey(), [
                    'order_id' => (int) $order->getKey(),
                ]);
            }

            $validated = ValidatesPayload::run(UpdateQuotationRequest::class, SyncAction::body($payload));
            $updated = $this->quotations->update($quotation, $validated);

            return SyncAction::result('quotation', (int) $updated->getKey());
        }

        $validated = ValidatesPayload::run(StoreQuotationRequest::class, SyncAction::body($payload));
        $created = $this->quotations->create($validated, $user->business_branch_id);

        return SyncAction::result('quotation', (int) $created->getKey(), [
            'quotation_number' => $created->quotation_number,
        ]);
    }
}
