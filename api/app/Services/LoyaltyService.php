<?php

namespace App\Services;

use App\Models\LoyaltyCard;
use App\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Manages loyalty points: earning on purchases and redeeming rewards.
 */
class LoyaltyService
{
    /**
     * Earn points on a completed sale.
     */
    public function earnPoints(int $loyaltyCardId, int $saleId, float $amount, string $reference = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($loyaltyCardId, $saleId, $amount, $reference) {
            $card = LoyaltyCard::findOrFail($loyaltyCardId);
            $program = $card->loyaltyProgram;

            $points = $amount * $program->points_per_currency;

            $card->increment('points_balance', $points);
            $card->update(['last_used_at' => now()]);

            return $card->transactions()->create([
                'sale_id' => $saleId,
                'type' => 'earn',
                'points' => $points,
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Burn (redeem) points for a reward.
     */
    public function burnPoints(int $loyaltyCardId, float $points, string $reference = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($loyaltyCardId, $points, $reference) {
            $card = LoyaltyCard::findOrFail($loyaltyCardId);

            if ($card->points_balance < $points) {
                throw new \Exception('Insufficient points balance.');
            }

            $card->decrement('points_balance', $points);
            $card->update(['last_used_at' => now()]);

            return $card->transactions()->create([
                'type' => 'burn',
                'points' => -$points,
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Adjust points manually (admin correction).
     */
    public function adjustPoints(int $loyaltyCardId, float $points, string $reference = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($loyaltyCardId, $points, $reference) {
            $card = LoyaltyCard::findOrFail($loyaltyCardId);
            $card->increment('points_balance', $points);
            $card->update(['last_used_at' => now()]);

            return $card->transactions()->create([
                'type' => 'adjust',
                'points' => $points,
                'reference' => $reference,
            ]);
        });
    }
}
