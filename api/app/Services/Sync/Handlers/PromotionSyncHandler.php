<?php

namespace App\Services\Sync\Handlers;

use App\Models\Promotion;
use App\Models\User;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Support\Auth\RolePermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;

/**
 * PromotionController validates inline and has no service. The rules here are
 * that same list, so an offline promotion is accepted on the same terms.
 */
class PromotionSyncHandler implements SyncHandler
{
    public function type(): string
    {
        return 'promotion';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync promotions.');
        }

        if (! RolePermissions::canManageDiscounts($user)) {
            throw new AuthorizationException('You cannot manage promotions.');
        }

        $action = SyncAction::name($payload);

        if ($action === 'delete') {
            $promotion = Promotion::query()->findOrFail(SyncAction::id($payload));
            $id = (int) $promotion->getKey();
            $promotion->delete();

            return SyncAction::result('promotion', $id);
        }

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'sometimes|in:active,inactive,expired',
            'max_uses' => 'nullable|integer|min:1',
        ];

        if ($action === 'update') {
            $promotion = Promotion::query()->findOrFail(SyncAction::id($payload));
            $validated = Validator::make(SyncAction::body($payload), $rules)->validate();
            $promotion->update($validated);

            return SyncAction::result('promotion', (int) $promotion->getKey());
        }

        $validated = Validator::make(SyncAction::body($payload), $rules)->validate();
        $promotion = Promotion::create($validated);

        return SyncAction::result('promotion', (int) $promotion->getKey());
    }
}
