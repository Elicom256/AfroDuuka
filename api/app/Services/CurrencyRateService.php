<?php

namespace App\Services;

use App\Models\Business;
use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyRateService
{
    /**
     * Fetch live market rates for every business, keyed to each business's base
     * currency. Rates are written per business because the table's business_id is
     * NOT NULL; the payload itself is fetched once and reused across tenants.
     *
     * @return array{success: bool, synced: int, businesses?: int, message: string}
     */
    public function syncAllBusinesses(?string $forcedBase = null, ?int $businessId = null): array
    {
        $query = Business::query()->with('country')->orderBy('id');

        if ($businessId !== null) {
            $query->whereKey($businessId);
        }

        $businesses = $query->get();

        if ($businesses->isEmpty()) {
            return ['success' => true, 'synced' => 0, 'businesses' => 0, 'message' => 'No businesses to sync.'];
        }

        // Group businesses by their base currency so we make one HTTP call per
        // currency instead of one per business.
        $groups = [];
        foreach ($businesses as $business) {
            $base = $forcedBase ?: ($business->country?->currency_code ?: config('currency.default_base', 'UGX'));
            $groups[$base][] = $business->id;
        }

        $syncedTotal = 0;
        $failed = 0;

        foreach ($groups as $base => $businessIds) {
            $rates = $this->fetchRates($base);

            if ($rates === null) {
                Log::error('Currency rate sync failed for base currency', ['base' => $base]);
                $failed++;
                continue;
            }

            foreach ($businessIds as $businessId) {
                $syncedTotal += $this->storeRatesForBusiness($businessId, $base, $rates);
            }
        }

        $success = $failed === 0;

        return [
            'success' => $success,
            'synced' => $syncedTotal,
            'businesses' => $businesses->count(),
            'message' => $success
                ? "Synced {$syncedTotal} currency rates across {$businesses->count()} businesses."
                : "Failed to sync {$failed} base currency group(s).",
        ];
    }

    /**
     * Fetch rates from the configured provider. Returns a map of target currency
     * code => rate, or null when the provider is unreachable / malformed.
     *
     * @return array<string, float>|null
     */
    public function fetchRates(string $baseCurrency): ?array
    {
        $config = config('currency.provider');
        $url = rtrim($config['url'], '/').'/'.$baseCurrency;

        try {
            $response = Http::timeout(15)->retry(2, 200)->get($url);
        } catch (\Throwable $e) {
            Log::warning('Currency provider request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Currency provider returned an error', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();

        if (empty($data['rates']) || ! is_array($data['rates'])) {
            return null;
        }

        return array_map('floatval', $data['rates']);
    }

    /**
     * Upsert rates for a single business. Existing rows for the same pair are
     * refreshed in place, so running the sync repeatedly never duplicates rows.
     *
     * @param  array<string, float>  $rates
     */
    public function storeRatesForBusiness(int $businessId, string $baseCurrency, array $rates): int
    {
        $today = now()->toDateString();
        $source = config('currency.provider.name', 'live');
        $count = 0;

        CurrencyRate::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('base_currency', $baseCurrency)
            ->delete();

        foreach ($rates as $target => $rate) {
            if ($target === $baseCurrency || $rate <= 0) {
                continue;
            }

            CurrencyRate::withoutGlobalScopes()->create([
                'business_id' => $businessId,
                'base_currency' => $baseCurrency,
                'target_currency' => strtoupper($target),
                'rate' => $rate,
                'source' => $source,
                'valid_from' => $today,
                'valid_to' => null,
            ]);
            $count++;
        }

        return $count;
    }
}